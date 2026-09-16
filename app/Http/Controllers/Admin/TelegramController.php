<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TelegramSubscriber;
use App\Services\TelegramNotifier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TelegramController extends Controller
{
    public function index()
    {
        return view('admin.telegram', [
            'subscribers' => TelegramSubscriber::query()->orderByDesc('active')->orderBy('created_at')->get(),
            'botUsername' => config('lozan.telegram_bot_username'),
            'configured' => (bool) config('lozan.telegram_bot_token'),
        ]);
    }

    public function store(Request $request, TelegramNotifier $telegram)
    {
        $data = $request->validate([
            'chat_id' => ['required', 'integer', 'not_in:0', Rule::unique('telegram_subscribers', 'chat_id')],
            'label' => ['nullable', 'string', 'max:80'],
        ], [
            'chat_id.unique' => lozan_t('admin.telegram.errDuplicate'),
        ]);

        $subscriber = TelegramSubscriber::query()->create([
            'chat_id' => (int) $data['chat_id'],
            'label' => $data['label'] ?? null,
            'active' => false,
        ]);
        $reached = $telegram->send($subscriber->chat_id, "✅ <b>تمت إضافتك إلى إشعارات طلبات لوذان.</b>\nYou have been added to Lozan order notifications.\n\nأرسل /stop للإلغاء.\nSend /stop to unsubscribe.");
        $subscriber->update(['active' => $reached]);

        return back()->with('telegram.notice', lozan_t($reached ? 'admin.telegram.addedActive' : 'admin.telegram.addedPending', [
            'name' => $subscriber->displayName(),
        ]));
    }

    public function test(TelegramSubscriber $subscriber, TelegramNotifier $telegram)
    {
        $reached = $telegram->send($subscriber->chat_id, "🔔 <b>رسالة تجريبية من لوذان.</b>\nTest message from Lozan admin — notifications are working.");
        if ($reached && ! $subscriber->active) {
            $subscriber->update(['active' => true]);
        }

        return back()->with('telegram.notice', lozan_t($reached ? 'admin.telegram.testSent' : 'admin.telegram.testFailed', [
            'name' => $subscriber->displayName(),
        ]));
    }

    public function destroy(TelegramSubscriber $subscriber)
    {
        $subscriber->delete();

        return back()->with('telegram.notice', lozan_t('admin.telegram.removed', ['name' => $subscriber->displayName()]));
    }
}
