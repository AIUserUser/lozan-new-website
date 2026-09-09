@extends('layouts.admin')

@section('content')
<h1>{{ lozan_t('admin.orders.title') }}</h1>
@if($orders->isEmpty())
    <p class="muted">{{ lozan_t('admin.orders.empty') }}</p>
@else
    @foreach($orders as $order)
        <article class="order-card">
            <header>
                <strong>{{ lozan_t('admin.orders.ref') }}{{ $order->shortRef() }}</strong>
                · {{ $order->customer_name }} · <a href="tel:{{ $order->phone }}">{{ $order->phone }}</a>
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
            <form method="post" action="{{ route('admin.orders.update', $order) }}">
                @csrf @method('PATCH')
                <label class="visually-hidden">{{ lozan_t('admin.orders.statusA11y') }}</label>
                <select name="status" class="input" onchange="this.form.submit()">
                    @foreach(['pending','confirmed','contacted','done'] as $st)
                        <option value="{{ $st }}" @selected($order->status === $st)>{{ lozan_t('admin.orders.status.'.$st) }}</option>
                    @endforeach
                </select>
            </form>
        </article>
    @endforeach
@endif
@endsection
