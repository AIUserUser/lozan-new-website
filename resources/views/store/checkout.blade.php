@extends('layouts.store')

@section('content')
<div class="checkout page-enter">
    <div class="container checkout__box">
        <h1>{{ lozan_t('checkout.title') }}</h1>
        <p class="muted">{{ lozan_t('checkout.intro') }}</p>
        @if(!count($items))
            <p>{{ lozan_t('checkout.bagEmpty') }} <a href="{{ locale_url('shop') }}">{{ lozan_t('checkout.goShop') }}</a></p>
        @else
            <p>{{ lozan_t('cart.subtotal') }}: <strong>{{ format_kwd($subtotal) }}</strong></p>
            <form class="form" method="post" action="{{ locale_url('checkout') }}">
                @csrf
                <div class="field">
                    <label for="name">{{ lozan_t('checkout.name') }}</label>
                    <input id="name" name="name" class="input" required maxlength="120" value="{{ old('name') }}" placeholder="{{ lozan_t('checkout.namePh') }}">
                </div>
                <div class="field">
                    <label for="country">{{ lozan_t('checkout.country') }}</label>
                    <select id="country" name="country" class="input">
                        @foreach(gcc_countries() as $c)
                            <option value="{{ $c['code'] }}" @selected(old('country', 'KW') === $c['code'])>
                                {{ $c['flag'] }} {{ app()->getLocale() === 'en' ? $c['en'] : $c['ar'] }} (+{{ $c['dial'] }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="phone">{{ lozan_t('checkout.phone') }}</label>
                    <input id="phone" name="phone" class="input" required value="{{ old('phone') }}" placeholder="{{ lozan_t('checkout.phonePh') }}">
                    @error('phone')<p class="err">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="phone_confirm">{{ lozan_t('checkout.phoneConfirm') }}</label>
                    <input id="phone_confirm" name="phone_confirm" class="input" required value="{{ old('phone_confirm') }}" placeholder="{{ lozan_t('checkout.phoneConfirmPh') }}">
                    @error('phone_confirm')<p class="err">{{ $message }}</p>@enderror
                </div>
                <div class="field field--check">
                    <input id="delivery" name="wants_delivery" type="checkbox" value="1" class="check" @checked(old('wants_delivery'))>
                    <label for="delivery">{{ lozan_t('checkout.delivery') }}</label>
                </div>
                <div class="field" id="address-field">
                    <label for="address">{{ lozan_t('checkout.addr') }}</label>
                    <textarea id="address" name="address" class="input" maxlength="800" placeholder="{{ lozan_t('checkout.addrPh') }}">{{ old('address') }}</textarea>
                    @error('address')<p class="err">{{ $message }}</p>@enderror
                </div>
                @error('cart')<p class="err">{{ $message }}</p>@enderror
                <button type="submit" class="btn btn--gold">{{ lozan_t('checkout.submit') }}</button>
                <p><a href="{{ locale_url('cart') }}">{{ lozan_t('checkout.backBag') }}</a></p>
            </form>
        @endif
    </div>
</div>
@endsection
