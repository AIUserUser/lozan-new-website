@extends('layouts.admin')

@section('content')
<h1>{{ lozan_t('admin.products.title') }}</h1>
<p><a class="btn btn--gold" href="{{ route('admin.products.create') }}">{{ lozan_t('admin.products.add') }}</a></p>
@if($products->isEmpty())
    <p class="muted">{{ lozan_t('admin.products.empty') }}</p>
@else
    <ul class="admin-list">
        @foreach($products as $p)
            <li class="admin-list__row">
                @if($p->coverUrl())
                    <img src="{{ $p->coverUrl() }}" alt="" width="48" height="64">
                @endif
                <div>
                    <strong>{{ $p->name }}</strong>
                    @if($p->name_en)<span class="muted"> / {{ $p->name_en }}</span>@endif
                    <p class="muted">{{ format_kwd($p->price) }} · {{ lozan_t('admin.products.categories.'.$p->category) }}</p>
                </div>
                <a href="{{ route('admin.products.edit', $p) }}">{{ lozan_t('admin.products.edit') }}</a>
                <form method="post" action="{{ route('admin.products.destroy', $p) }}" onsubmit="return confirm(@json(lozan_t('admin.products.confirmDelete')))">
                    @csrf @method('DELETE')
                    <button type="submit" class="line__remove">{{ lozan_t('admin.products.delete') }}</button>
                </form>
            </li>
        @endforeach
    </ul>
@endif
@endsection
