<header class="header">
    <div class="container header__bar">
        <a href="#main" class="skip-link">{{ lozan_t('nav.skip') }}</a>
        <a href="{{ locale_url('/') }}" class="header__brand" aria-label="{{ lozan_t('nav.homeA11y') }}">
            <img class="header__logo" src="/logo.png" alt="Lozan" width="48" height="48">
            <span class="header__name">Lozan</span>
        </a>
        <button type="button" class="header__burger" data-menu-toggle aria-expanded="false" aria-controls="lozan-mobile-menu" aria-label="{{ lozan_t('nav.menuOpen') }}">
            <span class="header__burger-bar"></span>
            <span class="header__burger-bar"></span>
            <span class="header__burger-bar"></span>
        </button>
        <div id="lozan-mobile-menu" class="header__row">
            <div class="lang" role="group" aria-label="{{ lozan_t('lang.switcher') }}">
                <a class="lang__btn {{ app()->getLocale() === 'ar' ? 'lang__btn--on' : '' }}" href="{{ switch_locale_url('ar') }}">{{ lozan_t('lang.ar') }}</a>
                <span class="lang__sep" aria-hidden="true">|</span>
                <a class="lang__btn {{ app()->getLocale() === 'en' ? 'lang__btn--on' : '' }}" href="{{ switch_locale_url('en') }}">{{ lozan_t('lang.en') }}</a>
            </div>
            <nav class="header__nav" aria-label="{{ lozan_t('nav.primaryNav') }}">
                <a href="{{ locale_url('/') }}" class="header__link">{{ lozan_t('nav.home') }}</a>
                <a href="{{ locale_url('shop') }}" class="header__link">{{ lozan_t('nav.shop') }}</a>
                <a href="{{ locale_url('cart') }}" class="header__link header__link--cart">
                    <span>{{ lozan_t('nav.bag') }}</span>
                    @if(cart_count())
                        <span class="header__count">{{ cart_count() }}</span>
                    @endif
                </a>
                <a href="{{ auth()->user()?->is_admin ? route('admin.orders') : route('admin.login') }}" class="header__link header__link--admin">{{ lozan_t('nav.admin') }}</a>
            </nav>
        </div>
    </div>
    <button type="button" class="header__scrim" data-menu-close hidden aria-label="{{ lozan_t('nav.menuClose') }}"></button>
</header>
