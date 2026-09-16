@extends('layouts.admin')

@section('content')
<h1>{{ lozan_t('admin.orders.title') }}</h1>

<div class="orders-toolbar">
    <nav class="filters filters--cat" aria-label="{{ lozan_t('admin.orders.filterA11y') }}">
        @foreach(\App\Http\Controllers\Admin\OrderController::FILTERS as $f)
            <a href="{{ route('admin.orders', array_filter(['status' => $f === 'all' ? null : $f, 'q' => $search ?: null])) }}"
               class="filters__btn {{ $filter === $f ? 'filters__btn--on' : '' }}"
               @if($filter === $f) aria-current="page" @endif>
                {{ lozan_t('admin.orders.filters.'.$f) }} <span class="orders-toolbar__count">{{ $counts[$f] }}</span>
            </a>
        @endforeach
    </nav>
    <form method="get" action="{{ route('admin.orders') }}" class="orders-toolbar__search" role="search">
        @if($filter !== 'all')<input type="hidden" name="status" value="{{ $filter }}">@endif
        <label class="visually-hidden" for="orders-q">{{ lozan_t('admin.orders.searchLabel') }}</label>
        <input id="orders-q" type="search" name="q" value="{{ $search }}" class="input" placeholder="{{ lozan_t('admin.orders.searchPlaceholder') }}">
        <button type="submit" class="btn btn--ghost">{{ lozan_t('admin.orders.searchSubmit') }}</button>
        @if($search !== '')
            <a class="muted" href="{{ route('admin.orders', array_filter(['status' => $filter === 'all' ? null : $filter])) }}">{{ lozan_t('admin.orders.searchClear') }}</a>
        @endif
    </form>
</div>

@if(session('orders.notice'))
    <p class="orders-notice" role="status">{{ session('orders.notice') }}</p>
@endif

@if($filter === 'discarded')
    <p class="muted">{{ lozan_t('admin.orders.discardedHint') }}</p>
@endif

@if($orders->isEmpty())
    <p class="muted">{{ $search !== '' || $filter !== 'all' ? lozan_t('admin.orders.emptyFiltered') : lozan_t('admin.orders.empty') }}</p>
@else
    @foreach($orders as $order)
        <article class="order-card {{ $order->isDiscarded() ? 'order-card--discarded' : '' }}">
            <header class="order-card__head">
                <div>
                    <strong>{{ lozan_t('admin.orders.ref') }}{{ $order->shortRef() }}</strong>
                    · {{ $order->customer_name }} · <a href="tel:{{ $order->phone }}" dir="ltr">{{ $order->phone }}</a>
                </div>
                @if($order->isDiscarded())
                    <span class="order-card__badge">{{ lozan_t('admin.orders.discardedBadge', ['date' => $order->discarded_at->timezone('Asia/Kuwait')->format('Y-m-d')]) }}</span>
                @endif
            </header>
            <p class="muted">{{ $order->wants_delivery ? lozan_t('admin.orders.delivery', ['addr' => $order->address ?: '—']) : lozan_t('admin.orders.pickup') }}</p>
            <p>{{ format_kwd($order->subtotal) }} · {{ $order->created_at?->timezone('Asia/Kuwait') }}</p>
            <ul>
                @foreach($order->items as $it)
                    <li>
                        {{ $it->name_ar }} × {{ $it->quantity }}
                        @if($it->size) · {{ format_eu_size($it->size) }} @endif
                        @if($it->backorder) · {{ lozan_t('admin.orders.backorderTag') }} @endif
                    </li>
                @endforeach
            </ul>
            <div class="order-card__actions">
                <form method="post" action="{{ route('admin.orders.update', $order) }}">
                    @csrf @method('PATCH')
                    <label class="visually-hidden" for="status-{{ $order->id }}">{{ lozan_t('admin.orders.statusA11y') }}</label>
                    <select id="status-{{ $order->id }}" name="status" class="input" onchange="this.form.submit()">
                        @foreach(\App\Models\Order::STATUSES as $st)
                            <option value="{{ $st }}" @selected($order->status === $st)>{{ lozan_t('admin.orders.status.'.$st) }}</option>
                        @endforeach
                    </select>
                </form>
                @if($order->isDiscarded())
                    <form method="post" action="{{ route('admin.orders.restore', $order) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn--ghost">{{ lozan_t('admin.orders.restore') }}</button>
                    </form>
                @else
                    <form method="post" action="{{ route('admin.orders.discard', $order) }}" onsubmit="return confirm(@json(lozan_t('admin.orders.confirmDiscard')))">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn--ghost order-card__discard">{{ lozan_t('admin.orders.discard') }}</button>
                    </form>
                @endif
            </div>
        </article>
    @endforeach
@endif
@endsection
