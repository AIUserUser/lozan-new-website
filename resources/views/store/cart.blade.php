@extends('layouts.store')

@section('content')
<div class="cart page-enter">
    <div class="container">
        <h1>{{ lozan_t('cart.title') }}</h1>
        @if(!count($items))
            <p class="muted">{{ lozan_t('cart.bagEmpty') }} <a href="{{ locale_url('shop') }}">{{ lozan_t('cart.shopMore') }}</a></p>
        @else
            <div class="wrap">
                <ul class="lines" role="list">
                    @foreach($items as $row)
                        <li class="line">
                            <div class="line__media">
                                @if(!empty($row['image_url']))
                                    <img src="{{ $row['image_url'] }}" alt="" width="96" height="120">
                                @else
                                    <div class="line__ph"></div>
                                @endif
                            </div>
                            <div class="line__text">
                                <h2 class="line__name">{{ app()->getLocale() === 'en' && !empty($row['name_en']) ? $row['name_en'] : ($row['name_ar'] ?? '') }}</h2>
                                @if(!empty($row['backorder']))
                                    <p class="line__backorder">{{ lozan_t('cart.backorderLine') }}</p>
                                @endif
                                @if(!empty($row['color']))
                                    <p class="muted">{{ lozan_t('cart.color', ['c' => color_label($row['color'])]) }}</p>
                                @endif
                                @if(!empty($row['size']))
                                    <p class="muted">{{ lozan_t('cart.size', ['s' => format_eu_size($row['size'])]) }}</p>
                                @endif
                                <p>{{ format_kwd($row['price']) }} · {{ lozan_t('cart.each') }}</p>
                            </div>
                            <div class="line__side">
                                <form method="post" action="{{ locale_url('cart/update') }}" class="qty-form">
                                    @csrf
                                    <input type="hidden" name="line_id" value="{{ $row['line_id'] }}">
                                    <div class="qty">
                                        <button type="submit" name="qty" value="{{ max(1, $row['quantity']-1) }}" class="qty__btn">−</button>
                                        <span class="qty__n">{{ $row['quantity'] }}</span>
                                        <button type="submit" name="qty" value="{{ min(99, $row['quantity']+1) }}" class="qty__btn">+</button>
                                    </div>
                                </form>
                                <p class="line__sub">{{ format_kwd($row['price'] * $row['quantity']) }}</p>
                                <form method="post" action="{{ locale_url('cart/remove') }}">
                                    @csrf
                                    <input type="hidden" name="line_id" value="{{ $row['line_id'] }}">
                                    <button type="submit" class="line__remove">{{ lozan_t('cart.remove') }}</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <aside class="sum">
                    <h2>{{ lozan_t('cart.summary') }}</h2>
                    <p>{{ lozan_t('cart.subtotal') }}: <strong>{{ format_kwd($subtotal) }}</strong></p>
                    <p class="muted">{{ lozan_t('cart.summaryNote') }}</p>
                    <a class="btn btn--gold" href="{{ locale_url('checkout') }}">{{ lozan_t('cart.proceed') }}</a>
                </aside>
            </div>
        @endif
    </div>
</div>
@endsection
