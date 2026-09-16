<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public const FILTERS = ['all', ...Order::STATUSES, 'discarded'];

    public function index(Request $request)
    {
        $filter = in_array($request->query('status'), self::FILTERS, true) ? $request->query('status') : 'all';
        $search = trim((string) $request->query('q', ''));

        $searched = Order::query()->when($search !== '', fn (Builder $q) => $this->search($q, $search));

        $counts = (clone $searched)->active()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $counts = collect(Order::STATUSES)->mapWithKeys(fn ($s) => [$s => (int) ($counts[$s] ?? 0)])
            ->prepend((int) $counts->sum(), 'all')
            ->put('discarded', (clone $searched)->discarded()->count());

        $orders = $searched
            ->when($filter === 'discarded', fn (Builder $q) => $q->discarded(), fn (Builder $q) => $q->active())
            ->when(in_array($filter, Order::STATUSES, true), fn (Builder $q) => $q->where('status', $filter))
            ->with('items')
            ->latest()
            ->get();

        return view('admin.orders', [
            'orders' => $orders,
            'filter' => $filter,
            'search' => $search,
            'counts' => $counts,
        ]);
    }

    public function update(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => 'required|in:'.implode(',', Order::STATUSES),
        ]);
        $order->update(['status' => $data['status']]);

        return back();
    }

    public function discard(Order $order)
    {
        $order->update(['discarded_at' => now()]);

        return back()->with('orders.notice', lozan_t('admin.orders.discardedNotice', ['ref' => $order->shortRef()]));
    }

    public function restore(Order $order)
    {
        $order->update(['discarded_at' => null]);

        return back()->with('orders.notice', lozan_t('admin.orders.restoredNotice', ['ref' => $order->shortRef()]));
    }

    private function search(Builder $query, string $term): Builder
    {
        $digits = preg_replace('/\D/', '', $term);
        $ref = ltrim($term, '#');

        return $query->where(function (Builder $q) use ($term, $digits, $ref) {
            $q->where('customer_name', 'like', '%'.$term.'%')
                ->orWhere('legacy_id', 'like', $ref.'%');
            if ($digits !== '') {
                $q->orWhere('phone', 'like', '%'.$digits.'%');
                if (ctype_digit($ref)) {
                    $q->orWhere('id', (int) $ref);
                }
            }
        });
    }
}
