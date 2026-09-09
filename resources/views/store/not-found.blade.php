@extends('layouts.store')

@section('content')
<div class="container page-enter">
    <h1>{{ lozan_t('notFound.title') }}</h1>
    <p class="muted">{{ lozan_t('notFound.body') }}</p>
    <a class="btn" href="{{ locale_url('/') }}">{{ lozan_t('notFound.home') }}</a>
</div>
@endsection
