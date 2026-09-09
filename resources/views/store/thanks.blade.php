@extends('layouts.store')

@section('content')
<div class="page-enter container checkout__box">
    <p class="hero__eyebrow">{{ lozan_t('orderSuccess.eyebrow') }}</p>
    <h1>{{ lozan_t('orderSuccess.title') }}</h1>
    @if($order)
        <p>{{ lozan_t('orderSuccess.ref') }} <strong>#{{ $order->shortRef() }}</strong></p>
        <p>
            @if($order->status === 'confirmed') {{ lozan_t('orderSuccess.statusConfirmed') }}
            @elseif($order->status === 'done') {{ lozan_t('orderSuccess.statusDone') }}
            @elseif($order->status === 'contacted') {{ lozan_t('orderSuccess.statusProgress') }}
            @else {{ lozan_t('orderSuccess.statusPending') }}
            @endif
        </p>
        <p class="muted">{{ $order->status === 'confirmed' ? lozan_t('orderSuccess.confirmedBody') : lozan_t('orderSuccess.body') }}</p>
        <p>{{ $order->wants_delivery ? lozan_t('orderSuccess.deliveryWithAddr', ['address' => $order->address]) : lozan_t('orderSuccess.pickupOrConfirm') }}</p>
        <p>{{ format_kwd($order->subtotal) }}</p>
        <a class="btn btn--gold" href="{{ locale_url('shop') }}">{{ lozan_t('orderSuccess.shop') }}</a>
    @else
        @if($mismatch ?? false)
            <p class="err">{{ lozan_t('orderSuccess.notFound') }}</p>
        @endif
        @if(!$orderId)
            <p class="muted">{{ lozan_t('orderSuccess.missingId') }}</p>
        @else
            <p class="muted">{{ lozan_t('orderSuccess.enterPhone') }}</p>
            <form method="post" action="{{ locale_url('order-confirmation') }}?id={{ $orderId }}" class="form">
                @csrf
                <div class="field">
                    <label for="phone">{{ lozan_t('orderSuccess.phoneLabel') }}</label>
                    <input id="phone" name="phone" class="input" required>
                </div>
                <button class="btn btn--gold" type="submit">{{ lozan_t('orderSuccess.check') }}</button>
            </form>
        @endif
        <p><a href="{{ locale_url('shop') }}">{{ lozan_t('orderSuccess.shop') }}</a></p>
    @endif
</div>
@endsection
