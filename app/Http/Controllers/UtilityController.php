<?php

namespace App\Http\Controllers;

use App\Models\TelegramSubscriber;
use App\Services\SitemapService;
use App\Services\TelegramNotifier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UtilityController extends Controller
{
    public function sitemap(SitemapService $sitemap): Response
    {
        return response($sitemap->render(), 200)->header('Content-Type', 'application/xml; charset=UTF-8');
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
        // "/start@lozan_kw_bot payload" -> "/start"
        $command = strtolower(preg_replace('/@\w+$/', '', explode(' ', $text)[0]));

        $subscriber = TelegramSubscriber::query()->where('chat_id', $chatId)->first();
        if (! $subscriber) {
            $telegram->send($chatId, "🔒 <b>هذا البوت مخصص لفريق لوذان فقط.</b>\nThis bot is for Lozan staff only.\n\nمعرّف تيليجرام الخاص بك · Your Telegram ID:\n<code>{$chatId}</code>\n\nأرسله إلى مسؤول المتجر لإضافتك.\nSend it to the store admin to be added.");

            return response()->json(['ok' => true]);
        }

        $subscriber->fill([
            'username' => $from['username'] ?? $subscriber->username,
            'first_name' => $from['first_name'] ?? $subscriber->first_name,
            'last_name' => $from['last_name'] ?? $subscriber->last_name,
        ]);

        if ($command === '/start') {
            $subscriber->active = true;
            $subscriber->save();
            $telegram->send($chatId, "✅ <b>تم الاشتراك في إشعارات الطلبات.</b>\nYou are now subscribed to order notifications.\n\nأرسل /stop للإلغاء.\nSend /stop to unsubscribe.");
        } elseif ($command === '/stop') {
            $subscriber->active = false;
            $subscriber->save();
            $telegram->send($chatId, "🛑 <b>تم إلغاء الاشتراك.</b>\nYou have unsubscribed.\n\nأرسل /start لإعادة الاشتراك.");
        } elseif ($command === '/status') {
            $subscriber->save();
            $telegram->send($chatId, $subscriber->active
                ? "📬 أنت مشترك حاليًا.\nYou are currently subscribed."
                : "📭 أنت غير مشترك.\nYou are not subscribed.\n\nأرسل /start للاشتراك.");
        } else {
            $subscriber->save();
            $telegram->send($chatId, "🤖 /start /stop /status");
        }

        return response()->json(['ok' => true]);
    }
}
