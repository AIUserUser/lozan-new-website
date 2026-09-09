@extends('layouts.store')

@section('content')
@php
    $gallery = $product->galleryUrls();
    $cover = $product->coverUrl();
    $inStock = $product->isInStock();
    $canBackorder = $product->canBackorder();
@endphp
<div class="product page-enter">
    <div class="container container--wide product__in">
        <div class="product__media">
            @if($cover)
                <img src="{{ $cover }}" alt="{{ $seo['image_alt'] ?? product_display_name($product) }}" class="product__img" id="product-main-img">
            @else
                <div class="product__ph"></div>
            @endif
            @if(count($gallery) > 1)
                <div class="product__thumbs" role="list">
                    @foreach($gallery as $i => $u)
                        <button type="button" class="product__thumb {{ $i === 0 ? 'product__thumb--on' : '' }}" data-thumb="{{ $u }}">
                            <img src="{{ $u }}" alt="{{ lozan_t('product.photoN', ['n' => $i+1]) }}" width="64" height="80" loading="lazy">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="product__info">
            @if($product->category)
                <p class="product__cat">{{ lozan_t('category.'.$product->category) }}</p>
            @endif
            <h1 class="product__name">{{ product_display_name($product) }}</h1>
            @if(!$inStock)
                <p class="product__status product__status--unavail" role="status">{{ lozan_t('product.unavailableBanner') }}</p>
            @endif
            <p class="product__price">
                @if(is_on_sale($product))
                    <span class="product__price-sale">{{ format_kwd($product->offer_price) }}</span>
                    <span class="product__price-reg">{{ format_kwd($product->price) }}</span>
                    <span class="product__price-tag">{{ lozan_t('product.saleBadge', ['pct' => discount_percent($product)]) }}</span>
                @else
                    {{ format_kwd($product->price) }}
                @endif
            </p>
            @if($product->description)
                <div class="product__desc">
                    @foreach(preg_split("/\n+/", trim($product->description)) as $line)
                        <p>{{ $line }}</p>
                    @endforeach
                </div>
            @endif

            <form method="post" action="{{ locale_url('product/'.$product->slug.'/cart') }}" class="product-form">
                @csrf
                @if($product->colors->isNotEmpty())
                    <div class="product__colors">
                        <p class="product__sub">{{ lozan_t('product.colorLabel') }}</p>
                        <div class="colorrow">
                            @foreach($product->colorArrays() as $i => $c)
                                <label class="colorchip">
                                    <input type="radio" name="color" value="{{ color_key($c) }}" {{ $i === 0 ? 'required checked' : 'required' }} hidden>
                                    @if(!empty($c['hex']))
                                        <span class="colorchip__sw" style="background: {{ $c['hex'] }}"></span>
                                    @endif
                                    <span class="colorchip__t">{{ color_label($c) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
                @if($product->sizes->isNotEmpty())
                    <div class="product__sizes">
                        <p class="product__sub">{{ lozan_t('product.sizeLabel') }}</p>
                        <p class="product__size-hint muted">{{ lozan_t('product.sizeHint') }}</p>
                        <div class="sizerow">
                            @foreach($product->sizeValues() as $i => $sz)
                                <label class="sizechip">
                                    <input type="radio" name="size" value="{{ $sz }}" {{ $i === 0 ? 'required' : 'required' }} hidden>
                                    {{ format_eu_size($sz) }}
                                </label>
                            @endforeach
                        </div>
                        @error('size')<p class="product__size-err">{{ $message }}</p>@enderror
                    </div>
                @endif

                <div class="product__row">
                    <div class="qty">
                        <button type="button" class="qty__btn" data-qty-delta="-1" aria-label="{{ lozan_t('product.dec') }}">−</button>
                        <input id="qty" class="qty__input" type="number" name="qty" value="1" min="1" max="99">
                        <button type="button" class="qty__btn" data-qty-delta="1" aria-label="{{ lozan_t('product.inc') }}">+</button>
                    </div>
                    @if($inStock)
                        <button type="submit" class="btn btn--gold">{{ lozan_t('product.addToBag') }}</button>
                    @elseif($canBackorder)
                        <input type="hidden" name="backorder" value="1">
                        <button type="submit" class="btn btn--gold">{{ lozan_t('product.orderAnyway') }}</button>
                    @endif
                </div>
                @if($canBackorder && !$inStock)
                    <p class="product__backorder-note muted">{{ lozan_t('product.backorderNote') }}</p>
                @elseif($product->isUnavailable())
                    <p class="product__unavail-note muted">{{ lozan_t('product.unavailableNote') }}</p>
                @endif
                @if(session('added'))
                    <p class="product__toast" role="status">
                        {{ session('added') === 'backorder' ? lozan_t('product.addedBackorderLine') : lozan_t('product.addedLine') }}
                        <a href="{{ locale_url('cart') }}">{{ lozan_t('product.viewBag') }}</a>
                    </p>
                @endif
            </form>
            <p><a href="{{ locale_url('shop') }}">{{ lozan_t('product.backShop') }}</a></p>
        </div>
    </div>
</div>
@endsection
