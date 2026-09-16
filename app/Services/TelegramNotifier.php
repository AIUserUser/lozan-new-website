<?php

namespace App\Services;

use App\Models\Order;
use App\Models\TelegramSubscriber;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotifier
{
    public function notifyOrder(Order $order): void
    {
        if (! config('lozan.telegram_bot_token')) {
            return;
        }
        $message = $this->buildMessage($order);
        foreach (TelegramSubscriber::query()->where('active', true)->pluck('chat_id') as $chatId) {
            $this->send($chatId, $message);
        }
    }

    /**
     * Send a message; returns whether Telegram accepted it. A chat that blocked
     * the bot or never started it is marked inactive.
     */
    public function send(int|string $chatId, string $text): bool
    {
        $token = config('lozan.telegram_bot_token');
        if (! $token) {
            return false;
        }
        $res = Http::asJson()->timeout(15)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ]);
        if ($res->successful()) {
            return true;
        }
        $code = (int) data_get($res->json(), 'error_code', $res->status());
        if ($code === 403 || ($code === 400 && str_contains((string) data_get($res->json(), 'description'), 'chat not found'))) {
            TelegramSubscriber::query()->where('chat_id', $chatId)->update(['active' => false]);
        }
        Log::warning('Telegram send failed', ['chat_id' => $chatId, 'body' => $res->body()]);

        return false;
    }

    private function buildMessage(Order $order): string
    {
        $order->loadMissing('items');
        $id = $this->e($order->shortRef());
        $name = $this->e($order->customer_name);
        $phone = $this->e($order->phone);
        $total = $this->e(number_format((float) $order->subtotal, 3).' KWD');
        $delivery = $order->wants_delivery
            ? '🚚 <b>توصيل · Delivery</b>'.($order->address ? "\n   ".$this->e($order->address) : '')
            : '🏬 <b>استلام · Pickup</b>';
        $hasBackorder = $order->items->contains(fn ($i) => $i->backorder);
        $banner = $hasBackorder
            ? '⏳ <b>يحتوي طلبات مسبقة · Includes backorder items</b> (~10 أيام)'
            : '';
        $items = $order->items->values()->map(function ($it, $idx) {
            $nameAr = $it->name_ar ?: $it->name_en ?: '—';
            $lines = [($idx + 1).'. <b>'.$this->e($nameAr).'</b>'];
            if ($it->name_en && $it->name_en !== $it->name_ar) {
                $lines[] = '   <i>'.$this->e($it->name_en).'</i>';
            }
            $lineTotal = ((float) $it->unit_price) * ((int) $it->quantity);
            $lines[] = '   × '.$it->quantity.' · '.$this->e(number_format($lineTotal, 3).' KWD');
            $color = trim(implode(' / ', array_filter([$it->color_ar, $it->color_en && $it->color_en !== $it->color_ar ? $it->color_en : null])));
            if ($color) {
                $lines[] = '   🎨 '.$this->e($color);
            }
            if ($it->size) {
                $lines[] = '   📏 '.$this->e(format_eu_size($it->size));
            }
            if ($it->backorder) {
                $lines[] = '   ⏳ <b>طلب مسبق · Backorder</b> — ~10 أيام / ~10 days';
            }

            return implode("\n", $lines);
        })->implode("\n\n");
        $created = $order->created_at?->timezone('Asia/Kuwait')->format('d/m/Y H:i');

        return implode("\n", array_filter([
            "🛍️ <b>طلب جديد · New order</b>  <code>#{$id}</code>",
            $banner,
            '',
            "👤 {$name}",
            "📞 <code>{$phone}</code>",
            $delivery,
            '',
            '📦 <b>المنتجات · Items</b>',
            $items ?: '—',
            '',
            "💰 <b>الإجمالي · Total:</b> {$total}",
            $created ? '🕒 <i>'.$this->e($created).'</i>' : '',
        ], fn ($l) => $l !== null));
    }

    private function e(mixed $s): string
    {
        return htmlspecialchars((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
