<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ lozan_t('admin.title') }} | Lozan</title>
    <link rel="icon" href="/favicon.ico">
    <link rel="stylesheet" href="/css/app.css">
    @stack('head')
</head>
<body>
<div class="admin">
    <aside class="side">
        <a href="{{ locale_url('/') }}" class="side__logo">
            <img src="/logo.png" alt="" width="40" height="40" class="side__img">
            <span class="side__name">Lozan</span>
        </a>
        <p class="side__sub">{{ lozan_t('admin.title') }}</p>
        <nav class="side__nav">
            <a href="{{ route('admin.analytics') }}" class="side__link {{ request()->routeIs('admin.analytics') ? 'side__link--on' : '' }}">{{ lozan_t('admin.nav.analytics') }}</a>
            <a href="{{ route('admin.orders') }}" class="side__link {{ request()->routeIs('admin.orders') ? 'side__link--on' : '' }}">{{ lozan_t('admin.nav.orders') }}</a>
            <a href="{{ route('admin.products') }}" class="side__link {{ request()->routeIs('admin.products*') ? 'side__link--on' : '' }}">{{ lozan_t('admin.nav.products') }}</a>
            <a href="{{ route('admin.telegram') }}" class="side__link {{ request()->routeIs('admin.telegram*') ? 'side__link--on' : '' }}">{{ lozan_t('admin.nav.telegram') }}</a>
        </nav>
        <form method="post" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="side__out">{{ lozan_t('admin.signOut') }}</button>
        </form>
    </aside>
    <div class="admin__body @hasSection('wide') admin__body--wide @endif">
        @yield('content')
    </div>
</div>
@stack('scripts')
</body>
</html>
