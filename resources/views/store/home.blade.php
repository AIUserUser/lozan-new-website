@extends('layouts.store')

@section('head')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => ['Store', 'ClothingStore'],
    'name' => lozan_t('meta.siteName'),
    'alternateName' => [lozan_t('meta.siteNameAlt'), 'لوزان', 'Lozan boutique'],
    'description' => lozan_t('meta.jsonldDescription'),
    'image' => url('/logo.png'),
    'logo' => url('/logo.png'),
    'url' => locale_url('/'),
    'currenciesAccepted' => 'KWD',
    'paymentAccepted' => 'Cash on delivery',
    'address' => [
        ['@type' => 'PostalAddress', 'addressLocality' => 'الفروانية', 'addressRegion' => 'محافظة الفروانية', 'addressCountry' => 'KW', 'inLanguage' => 'ar'],
        ['@type' => 'PostalAddress', 'addressLocality' => 'Farwaniya', 'addressRegion' => 'Al Farwaniyah Governorate', 'addressCountry' => 'KW', 'inLanguage' => 'en'],
    ],
    'geo' => ['@type' => 'GeoCoordinates', 'latitude' => 29.2775, 'longitude' => 47.9583],
    'areaServed' => [
        ['@type' => 'Country', 'name' => 'الكويت', 'sameAs' => 'https://www.wikidata.org/wiki/Q817'],
        ['@type' => 'Country', 'name' => 'Kuwait', 'sameAs' => 'https://www.wikidata.org/wiki/Q817'],
    ],
    'knowsLanguage' => ['ar', 'en'],
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endsection

@section('content')
<div class="home">
    <section class="hero" aria-label="{{ lozan_t('home.heroA11y') }}">
        <div class="container hero__in">
            <p class="hero__eyebrow">{{ lozan_t('home.eyebrow') }}</p>
            <h1 class="hero__headline">{{ lozan_t('home.headline') }}</h1>
            <p class="hero__lead">{{ lozan_t('home.lead') }}</p>
            <div class="hero__cta">
                <a href="{{ locale_url('shop') }}" class="btn btn--gold">{{ lozan_t('home.ctaMain') }}</a>
                <a href="{{ locale_url('shop') }}" class="btn btn--ghost">{{ lozan_t('home.ctaAll') }}</a>
            </div>
        </div>
        <div class="hero__stripe" aria-hidden="true"></div>
    </section>

    <section class="section" aria-label="{{ lozan_t('home.sectionA11y') }}">
        <div class="container">
            @if($allProducts->isEmpty())
                <h2>{{ lozan_t('home.emptyTitle') }}</h2>
                <p class="muted">{{ lozan_t('home.emptyBefore') }} <a href="{{ locale_url('shop') }}">{{ lozan_t('home.emptyLink') }}</a> {{ lozan_t('home.emptyAfter') }}</p>
            @else
                <div class="section__head">
                    <h2>{{ lozan_t('home.sectionTitle') }}</h2>
                    <div class="section__actions">
                        @include('partials.filters', ['formAction' => locale_url('/')])
                        <a href="{{ locale_url('shop') }}" class="section__all">{{ lozan_t('common.viewAll') }}</a>
                    </div>
                </div>
                @if($products->isEmpty())
                    <p class="muted">{{ lozan_t('shop.emptyFiltered') }}</p>
                @else
                    <div class="grid">
                        @foreach($products as $product)
                            @include('partials.product-card', ['product' => $product])
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </section>
</div>
@endsection
