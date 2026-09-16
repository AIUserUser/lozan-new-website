@extends('layouts.admin')

@php
    // Keep bot commands readable inside Arabic (RTL) text.
    $cmd = fn (string $html) => preg_replace('#\x{200E}?(/(?:start|stop|status))#u', '<bdi dir="ltr">$1</bdi>', $html);
@endphp

@section('content')
<h1>{{ lozan_t('admin.telegram.title') }}</h1>
<p class="muted">{{ lozan_t('admin.telegram.subtitle') }}</p>

@unless($configured)
    <p class="orders-notice" role="alert">{{ lozan_t('admin.telegram.notConfigured') }}</p>
@endunless

@if(session('telegram.notice'))
    <p class="orders-notice" role="status">{{ session('telegram.notice') }}</p>
@endif

<section class="tg-steps">
    <h2>{{ lozan_t('admin.telegram.howTitle') }}</h2>
    <ol>
        <li>{!! str_replace('__BOT__', '<a href="https://t.me/'.e($botUsername).'" target="_blank" rel="noopener" dir="ltr">@'.e($botUsername).'</a>', $cmd(e(lozan_t('admin.telegram.how1', ['bot' => '__BOT__'])))) !!}</li>
        <li>{{ lozan_t('admin.telegram.how2') }}</li>
        <li>{{ lozan_t('admin.telegram.how3') }}</li>
    </ol>
</section>

<form method="post" action="{{ route('admin.telegram.store') }}" class="tg-add">
    @csrf
    <div class="field">
        <label for="tg-chat-id">{{ lozan_t('admin.telegram.idLabel') }}</label>
        <input id="tg-chat-id" name="chat_id" class="input" inputmode="numeric" pattern="-?[0-9]+" required dir="ltr" value="{{ old('chat_id') }}" placeholder="123456789">
        @error('chat_id')<p class="err">{{ $message }}</p>@enderror
    </div>
    <div class="field">
        <label for="tg-label">{{ lozan_t('admin.telegram.nameLabel') }}</label>
        <input id="tg-label" name="label" class="input" maxlength="80" value="{{ old('label') }}" placeholder="{{ lozan_t('admin.telegram.namePlaceholder') }}">
        @error('label')<p class="err">{{ $message }}</p>@enderror
    </div>
    <button type="submit" class="btn btn--gold">{{ lozan_t('admin.telegram.add') }}</button>
</form>

<h2>{{ lozan_t('admin.telegram.listTitle', ['count' => $subscribers->count()]) }}</h2>
@if($subscribers->isEmpty())
    <p class="muted">{{ lozan_t('admin.telegram.empty') }}</p>
@else
    <ul class="admin-list">
        @foreach($subscribers as $sub)
            <li class="admin-list__row tg-row">
                <div class="tg-row__who">
                    <strong>{{ $sub->displayName() }}</strong>
                    <p class="muted">
                        @if($sub->username)<span dir="ltr">{{ '@'.$sub->username }}</span> · @endif
                        <span dir="ltr">ID {{ $sub->chat_id }}</span>
                    </p>
                </div>
                <span class="tg-status {{ $sub->active ? 'tg-status--on' : '' }}">
                    {!! $cmd(e($sub->active ? lozan_t('admin.telegram.statusActive') : lozan_t('admin.telegram.statusPending'))) !!}
                </span>
                <form method="post" action="{{ route('admin.telegram.test', $sub) }}">
                    @csrf
                    <button type="submit" class="line__remove">{{ lozan_t('admin.telegram.test') }}</button>
                </form>
                <form method="post" action="{{ route('admin.telegram.destroy', $sub) }}" onsubmit="return confirm(@json(lozan_t('admin.telegram.confirmRemove', ['name' => $sub->displayName()])))">
                    @csrf @method('DELETE')
                    <button type="submit" class="line__remove">{{ lozan_t('admin.telegram.remove') }}</button>
                </form>
            </li>
        @endforeach
    </ul>
@endif
@endsection
