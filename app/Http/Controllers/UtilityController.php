<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\TelegramSubscriber;
use App\Services\TelegramNotifier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UtilityController extends Controller
{
    public function sitemap(): Response
    {
        $products = Product::query()->published()->get();
        $urls = [];
        foreach (['ar' => '', 'en' => 'en'] as $locale => $prefix) {
            $base = $prefix === '' ? url('/') : url('/en');
            $urls[] = ['loc' => $base, 'changefreq' => 'daily', 'priority' => '1.0'];
            $urls[] = ['loc' => locale_url('shop', $locale), 'changefreq' => 'daily', 'priority' => '0.9'];
            foreach ($products as $p) {
                $urls[] = [
                    'loc' => product_url($p, $locale),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                    'lastmod' => optional($p->updated_at)->toAtomString(),
                ];
            }
        }
        $xml = view('store.sitemap', ['urls' => $urls])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $body = "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /cart\nDisallow: /checkout\nDisallow: /en/cart\nDisallow: /en/checkout\nDisallow: /order-confirmation\nDisallow: /en/order-confirmation\nSitemap: ".url('/sitemap.xml')."\n";

        return response($body, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function telegramWebhook(Request $request, TelegramNotifier $telegram)
    {
        $secret = (string) config('lozan.telegram_webhook_secret');
        $header = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');
        if ($secret === '' || ! hash_equals($secret, $header)) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        $message = $request->input('message') ?? $request->input('edited_message');
        $text = trim((string) data_get($message, 'text', ''));
        $chatId = data_get($message, 'chat.id');
        $from = data_get($message, 'from', []);
        if (! $chatId || $text === '') {
            return response()->json(['ok' => true]);
        }
        $command = strtolower(explode(' ', $text)[0]);

        if ($command === '/start') {
            TelegramSubscriber::query()->updateOrCreate(
                ['chat_id' => $chatId],
                [
                    'username' => $from['username'] ?? null,
                    'first_name' => $from['first_name'] ?? null,
                    'last_name' => $from['last_name'] ?? null,
                    'active' => true,
                ]
            );
            $telegram->send($chatId, "✅ <b>تم الاشتراك في إشعارات الطلبات.</b>\nYou are now subscribed to order notifications.\n\nأرسل /stop للإلغاء.\nSend /stop to unsubscribe.");
        } elseif ($command === '/stop') {
            TelegramSubscriber::query()->where('chat_id', $chatId)->update(['active' => false]);
            $telegram->send($chatId, "🛑 <b>تم إلغاء الاشتراك.</b>\nYou have unsubscribed.\n\nأرسل /start لإعادة الاشتراك.");
        } elseif ($command === '/status') {
            $active = TelegramSubscriber::query()->where('chat_id', $chatId)->where('active', true)->exists();
            $telegram->send($chatId, $active
                ? "📬 أنت مشترك حاليًا.\nYou are currently subscribed."
                : "📭 أنت غير مشترك.\nYou are not subscribed.\n\nأرسل /start للاشتراك.");
        } else {
            $telegram->send($chatId, "🤖 /start /stop /status");
        }

        return response()->json(['ok' => true]);
    }
}
