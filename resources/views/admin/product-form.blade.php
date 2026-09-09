@extends('layouts.admin')

@section('content')
<h1>{{ $product ? lozan_t('admin.form.editTitle') : lozan_t('admin.form.newTitle') }}</h1>
<p><a href="{{ route('admin.products') }}">{{ lozan_t('admin.form.back') }}</a></p>
<form class="form" method="post" enctype="multipart/form-data" action="{{ $product ? route('admin.products.update', $product) : route('admin.products.store') }}">
    @csrf
    @if($product) @method('PUT') @endif
    <div class="field">
        <label>{{ lozan_t('admin.form.nameAr') }}</label>
        <input name="name" class="input" required value="{{ old('name', $product->name ?? '') }}">
        @error('name')<p class="err">{{ $message }}</p>@enderror
    </div>
    <div class="field">
        <label>{{ lozan_t('admin.form.nameEn') }}</label>
        <input name="name_en" class="input" value="{{ old('name_en', $product->name_en ?? '') }}">
    </div>
    <div class="field">
        <label>{{ lozan_t('admin.form.description') }}</label>
        <textarea name="description" class="input" rows="4" placeholder="{{ lozan_t('admin.form.descriptionPh') }}">{{ old('description', $product->description ?? '') }}</textarea>
    </div>
    <div class="field">
        <label>{{ lozan_t('admin.form.price', ['currency' => 'KWD']) }}</label>
        <input name="price" type="number" step="0.001" min="0" class="input" required value="{{ old('price', $product->price ?? '') }}">
        @error('price')<p class="err">{{ $message }}</p>@enderror
    </div>
    <div class="field">
        <label>{{ lozan_t('admin.form.offerPrice', ['currency' => 'KWD']) }}</label>
        <input name="offer_price" type="number" step="0.001" min="0" class="input" value="{{ old('offer_price', $product->offer_price ?? '') }}" placeholder="{{ lozan_t('admin.form.offerPricePh') }}">
        @error('offer_price')<p class="err">{{ $message }}</p>@enderror
    </div>
    <div class="field">
        <label>{{ lozan_t('admin.form.category') }}</label>
        <select name="category" class="input">
            <option value="gown" @selected(old('category', $product->category ?? 'gown')==='gown')>{{ lozan_t('admin.form.catGown') }}</option>
            <option value="party" @selected(old('category', $product->category ?? '')==='party')>{{ lozan_t('admin.form.catParty') }}</option>
            <option value="all" @selected(old('category', $product->category ?? '')==='all')>{{ lozan_t('admin.form.catAll') }}</option>
        </select>
    </div>
    <div class="field field--check">
        <input type="checkbox" name="published" value="1" @checked(old('published', $product->published ?? true))>
        <label>{{ lozan_t('admin.form.published') }}</label>
    </div>
    <div class="field">
        <label>{{ lozan_t('admin.form.stockStatus') }}</label>
        <select name="stock_status" class="input">
            <option value="in_stock" @selected(old('stock_status', $product->stock_status ?? 'in_stock')==='in_stock')>{{ lozan_t('admin.form.stockInStock') }}</option>
            <option value="backorder" @selected(old('stock_status', $product->stock_status ?? '')==='backorder')>{{ lozan_t('admin.form.stockBackorder') }}</option>
            <option value="unavailable" @selected(old('stock_status', $product->stock_status ?? '')==='unavailable')>{{ lozan_t('admin.form.stockUnavailable') }}</option>
        </select>
    </div>
    <h2>{{ lozan_t('admin.form.colorsTitle') }}</h2>
    <p class="muted">{{ lozan_t('admin.form.colorsHint') }}</p>
    <div id="colors">
        @php $colors = old('color_ar') ? collect(old('color_ar'))->map(fn($ar,$i)=>['name_ar'=>$ar,'name_en'=>old('color_en.'.$i),'hex'=>old('color_hex.'.$i)]) : ($product?->colors ?? collect()); @endphp
        @foreach($colors as $c)
            <div class="color-row">
                <input name="color_ar[]" class="input" placeholder="{{ lozan_t('admin.form.colorArPh') }}" value="{{ is_array($c) ? ($c['name_ar'] ?? $c['ar'] ?? '') : $c->name_ar }}">
                <input name="color_en[]" class="input" placeholder="{{ lozan_t('admin.form.colorEnPh') }}" value="{{ is_array($c) ? ($c['name_en'] ?? $c['en'] ?? '') : $c->name_en }}">
                <input name="color_hex[]" class="input" placeholder="#hex" value="{{ is_array($c) ? ($c['hex'] ?? '') : $c->hex }}">
            </div>
        @endforeach
        <div class="color-row">
            <input name="color_ar[]" class="input" placeholder="{{ lozan_t('admin.form.colorArPh') }}">
            <input name="color_en[]" class="input" placeholder="{{ lozan_t('admin.form.colorEnPh') }}">
            <input name="color_hex[]" class="input" placeholder="#hex">
        </div>
    </div>
    <h2>{{ lozan_t('admin.form.sizesTitle') }}</h2>
    @foreach(eu_size_presets() as $sz)
        <label class="check-inline">
            <input type="checkbox" name="sizes[]" value="{{ $sz }}" @checked(in_array((string)$sz, old('sizes', $product?->sizeValues() ?? []), true))>
            EU {{ $sz }}
        </label>
    @endforeach
    <h2>{{ lozan_t('admin.form.imagesTitle') }}</h2>
    <p class="muted">{{ lozan_t('admin.form.imagesHint') }}</p>
    @if($product)
        @foreach($product->images as $i => $img)
            <label class="keep-img">
                <input type="checkbox" name="keep_images[]" value="{{ $img->id }}" checked>
                <img src="{{ $img->url() }}" alt="" width="64" height="80">
                <input type="radio" name="cover_index" value="{{ $i }}" @checked($product->cover_index === $i)> {{ lozan_t('admin.form.cover') }}
            </label>
        @endforeach
    @endif
    <input type="file" name="images[]" accept="image/*" multiple>
    <p><button class="btn btn--gold" type="submit">{{ lozan_t('admin.form.save') }}</button>
    <a class="btn btn--ghost" href="{{ route('admin.products') }}">{{ lozan_t('admin.form.cancel') }}</a></p>
</form>
@endsection
