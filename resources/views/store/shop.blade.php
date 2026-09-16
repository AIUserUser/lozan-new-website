@extends('layouts.store')

@section('content')
<div class="shop page-enter">
    <div class="container">
        <header class="head">
            <h1>{{ lozan_t('shop.title') }}</h1>
            <p class="muted">{{ lozan_t('shop.subtitle') }}</p>
            <div class="shop-toolbar">
                @include('partials.filters', ['formAction' => locale_url('shop'), 'showCategory' => true, 'resultCount' => $products->count()])
            </div>
        </header>
        @if($allProducts->isEmpty())
            <p class="muted">{{ lozan_t('shop.empty') }}</p>
        @elseif($products->isEmpty())
            <p class="muted">{{ lozan_t('shop.emptyFiltered') }}</p>
        @else
            <div class="grid">
                @foreach($products as $product)
                    @include('partials.product-card', ['product' => $product])
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
