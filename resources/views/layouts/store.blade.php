<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seo['title'] ?? lozan_t('meta.defaultFullTitle') }}</title>
    <meta name="description" content="{{ $seo['description'] ?? lozan_t('meta.defaultDescription') }}">
    <meta name="keywords" content="{{ $seo['keywords'] ?? lozan_t('meta.keywords') }}">
    <meta name="robots" content="{{ $seo['robots'] ?? 'index,follow' }}">
    <link rel="canonical" href="{{ $seo['canonical'] ?? url()->current() }}">
    <link rel="alternate" hreflang="ar" href="{{ $seo['hreflang_ar'] ?? locale_url('', 'ar') }}">
    <link rel="alternate" hreflang="en" href="{{ $seo['hreflang_en'] ?? locale_url('', 'en') }}">
    <link rel="alternate" hreflang="x-default" href="{{ $seo['hreflang_ar'] ?? locale_url('', 'ar') }}">
    <meta property="og:site_name" content="{{ lozan_t('meta.siteName') }}">
    <meta property="og:title" content="{{ $seo['title'] ?? lozan_t('meta.defaultFullTitle') }}">
    <meta property="og:description" content="{{ $seo['description'] ?? lozan_t('meta.defaultDescription') }}">
    <meta property="og:type" content="{{ $seo['og_type'] ?? 'website' }}">
    <meta property="og:url" content="{{ $seo['canonical'] ?? url()->current() }}">
    <meta property="og:locale" content="{{ lozan_t('meta.ogLocale') }}">
    <meta property="og:locale:alternate" content="{{ lozan_t('meta.ogLocaleAlternate') }}">
    <meta property="og:image" content="{{ $seo['image'] ?? url('/logo.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="geo.region" content="KW-FA">
    <meta name="geo.placename" content="{{ lozan_t('meta.geoPlacename') }}">
    <link rel="icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=DM+Sans:wght@400;500;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css">
    @isset($jsonLd)
        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
    @endisset
    @yield('head')
</head>
<body>
    @include('partials.header')
    <main id="main" class="main page-enter" tabindex="-1">
        @yield('content')
    </main>
    @include('partials.footer')
    <script src="/js/store.js" defer></script>
</body>
</html>
