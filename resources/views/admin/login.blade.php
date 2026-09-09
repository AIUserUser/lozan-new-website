<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex,nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ lozan_t('admin.login.title') }}</title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
<div class="container checkout__box" style="padding:4rem 0">
    <h1>{{ lozan_t('admin.login.title') }}</h1>
    <p class="muted">{{ lozan_t('admin.login.subtitle') }}</p>
    <form method="post" action="{{ route('admin.login') }}" class="form">
        @csrf
        <div class="field">
            <label for="email">{{ lozan_t('admin.login.email') }}</label>
            <input id="email" type="email" name="email" class="input" value="{{ old('email') }}" required>
        </div>
        <div class="field">
            <label for="password">{{ lozan_t('admin.login.password') }}</label>
            <input id="password" type="password" name="password" class="input" required>
        </div>
        @error('email')<p class="err">{{ $message }}</p>@enderror
        <button class="btn btn--gold" type="submit">{{ lozan_t('admin.login.submit') }}</button>
    </form>
    <p><a href="{{ locale_url('/') }}">{{ lozan_t('admin.login.back') }}</a></p>
</div>
</body>
</html>
