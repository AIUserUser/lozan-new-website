<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\TelegramSubscriber;
use App\Models\User;
use App\Services\TelegramNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['lozan.telegram_bot_token' => 'test-token', 'lozan.telegram_webhook_secret' => 'hook-secret']);
    }

    private function telegramAccepts(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    }

    private function webhook(int $chatId, string $text)
    {
        return $this->withHeaders(['X-Telegram-Bot-Api-Secret-Token' => 'hook-secret'])
            ->postJson('/telegram/webhook', [
                'message' => ['text' => $text, 'chat' => ['id' => $chatId], 'from' => ['id' => $chatId, 'username' => 'staffer', 'first_name' => 'Sam']],
            ]);
    }

    private function admin(): User
    {
        return User::query()->create(['name' => 'Admin', 'email' => 'a@example.com', 'password' => 'secret-pass', 'is_admin' => true]);
    }

    public function test_unknown_user_only_gets_their_id_and_is_not_subscribed(): void
    {
        $this->telegramAccepts();
        $this->webhook(555, '/start')->assertOk();

        $this->assertSame(0, TelegramSubscriber::query()->count());
        Http::assertSent(fn (Request $r) => $r['chat_id'] === 555 && str_contains($r['text'], '<code>555</code>') && str_contains($r['text'], 'staff only'));
    }

    public function test_listed_user_can_start_and_stop(): void
    {
        $this->telegramAccepts();
        TelegramSubscriber::query()->create(['chat_id' => 777, 'label' => 'Faris', 'active' => false]);

        $this->webhook(777, '/start@lozan_kw_bot')->assertOk();
        $sub = TelegramSubscriber::query()->where('chat_id', 777)->sole();
        $this->assertTrue($sub->active);
        $this->assertSame('staffer', $sub->username);
        $this->assertSame('Faris', $sub->displayName());

        $this->webhook(777, '/stop')->assertOk();
        $this->assertFalse($sub->fresh()->active);
    }

    public function test_order_notifications_go_only_to_active_listed_chats(): void
    {
        $this->telegramAccepts();
        TelegramSubscriber::query()->create(['chat_id' => 1, 'active' => true]);
        TelegramSubscriber::query()->create(['chat_id' => 2, 'active' => false]);
        $order = Order::query()->create(['customer_name' => 'Sara', 'phone' => '+96551234567', 'subtotal' => 10, 'status' => 'pending']);

        app(TelegramNotifier::class)->notifyOrder($order);

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $r) => $r['chat_id'] === 1 && str_contains($r['text'], 'Sara'));
    }

    public function test_admin_adds_user_and_welcome_message_activates_them(): void
    {
        $this->telegramAccepts();
        $admin = $this->admin();
        $this->actingAs($admin)->from('/admin/telegram')
            ->post('/admin/telegram', ['chat_id' => '123456789', 'label' => 'Faris'])
            ->assertRedirect('/admin/telegram');

        $sub = TelegramSubscriber::query()->sole();
        $this->assertSame(123456789, $sub->chat_id);
        $this->assertTrue($sub->active);
        Http::assertSent(fn (Request $r) => $r['chat_id'] === 123456789);

        $this->actingAs($admin)->from('/admin/telegram')
            ->post('/admin/telegram', ['chat_id' => '123456789'])
            ->assertSessionHasErrors('chat_id');

        $sub->update(['username' => 'staffer']);
        $this->actingAs($admin)->get('/admin/telegram')->assertOk()->assertSee('Faris')->assertSee('@staffer')->assertDontSee('$sub->username', false);
    }

    public function test_user_who_never_started_bot_stays_pending(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'error_code' => 400, 'description' => 'Bad Request: chat not found'], 400)]);

        $this->actingAs($this->admin())->post('/admin/telegram', ['chat_id' => '-100200300', 'label' => 'Staff group']);

        $this->assertFalse(TelegramSubscriber::query()->where('chat_id', -100200300)->sole()->active);
    }

    public function test_guests_cannot_manage_telegram_users(): void
    {
        $this->get('/admin/telegram')->assertRedirect(route('admin.login'));
        $this->post('/admin/telegram', ['chat_id' => '1'])->assertRedirect(route('admin.login'));
        $this->assertSame(0, TelegramSubscriber::query()->count());
    }

    public function test_webhook_rejects_wrong_secret(): void
    {
        $this->withHeaders(['X-Telegram-Bot-Api-Secret-Token' => 'nope'])->postJson('/telegram/webhook', [])->assertUnauthorized();
    }
}
