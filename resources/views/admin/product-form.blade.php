@extends('layouts.admin')

@section('content')
<h1>{{ $product ? lozan_t('admin.form.editTitle') : lozan_t('admin.form.newTitle') }}</h1>
<p><a href="{{ route('admin.products') }}">{{ lozan_t('admin.form.back') }}</a></p>
<form class="form form--wide" method="post" enctype="multipart/form-data" action="{{ $product ? route('admin.products.update', $product) : route('admin.products.store') }}">
    @csrf
    @if($product) @method('PUT') @endif
    <div class="field">
        <label>{{ lozan_t('admin.form.nameAr') }}</label>
        <input id="f-name" name="name" class="input" required value="{{ old('name', $product->name ?? '') }}">
        @error('name')<p class="err">{{ $message }}</p>@enderror
    </div>
    <div class="field">
        <label>{{ lozan_t('admin.form.nameEn') }}</label>
        <input id="f-name-en" name="name_en" class="input" dir="ltr" value="{{ old('name_en', $product->name_en ?? '') }}">
    </div>
    <div class="field">
        <label for="f-description">{{ lozan_t('admin.form.description') }}</label>
        <textarea id="f-description" name="description" class="input" rows="4" placeholder="{{ lozan_t('admin.form.descriptionPh') }}">{{ old('description', $product->description ?? '') }}</textarea>
    </div>
    <div class="field">
        <label for="f-description-en">{{ lozan_t('admin.form.descriptionEn') }}</label>
        <textarea id="f-description-en" name="description_en" class="input" rows="4" dir="ltr" placeholder="{{ lozan_t('admin.form.descriptionEnPh') }}">{{ old('description_en', $product->description_en ?? '') }}</textarea>
        <p class="field__hint muted">{{ lozan_t('admin.form.descriptionEnHint') }}</p>
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

    @php
        $seoKeys = ['slug', 'seo_title_ar', 'seo_title_en', 'seo_description_ar', 'seo_description_en', 'seo_noindex', 'brand', 'sku', 'gtin', 'mpn', 'material'];
        $advancedOpen = collect($seoKeys)->contains(fn ($k) => $errors->has($k));
        $seoVal = fn (string $k) => old($k, $product->{$k} ?? '');
        $siteSuffix = [
            'ar' => ' | '.lozan_t('meta.siteName', [], 'ar').' — '.lozan_t('meta.geoPlacename', [], 'ar'),
            'en' => ' | '.lozan_t('meta.siteName', [], 'en').' — '.lozan_t('meta.geoPlacename', [], 'en'),
        ];
    @endphp
    <details class="advanced" @if($advancedOpen) open @endif>
        <summary class="advanced__summary">
            <span>{{ lozan_t('admin.form.seo.title') }}</span>
            <span class="muted">{{ lozan_t('admin.form.seo.summaryHint') }}</span>
        </summary>

        <div class="advanced__body" data-seo-form
             data-base-url="{{ url('/') }}"
             data-suffix-ar="{{ $siteSuffix['ar'] }}"
             data-suffix-en="{{ $siteSuffix['en'] }}"
             data-current-slug="{{ $product->slug ?? '' }}">

            <h3>{{ lozan_t('admin.form.seo.searchTitle') }}</h3>
            <p class="muted">{{ lozan_t('admin.form.seo.searchHint') }}</p>

            <div class="field">
                <label for="f-slug">{{ lozan_t('admin.form.seo.slug') }}</label>
                <div class="advanced__slug" dir="ltr">
                    <span class="muted">{{ url('/product') }}/</span>
                    <input id="f-slug" name="slug" class="input" dir="ltr" value="{{ $seoVal('slug') }}" placeholder="{{ $product->slug ?? lozan_t('admin.form.seo.slugAuto') }}" pattern="[a-z0-9-]*" maxlength="191">
                </div>
                <p class="field__hint muted">{{ $product ? lozan_t('admin.form.seo.slugHintEdit') : lozan_t('admin.form.seo.slugHintNew') }}</p>
                @error('slug')<p class="err">{{ $message }}</p>@enderror
            </div>

            <div class="advanced__grid">
                @foreach(['ar' => 'rtl', 'en' => 'ltr'] as $loc => $dir)
                    <fieldset class="advanced__lang">
                        <legend>{{ lozan_t('admin.form.seo.lang'.ucfirst($loc)) }}</legend>
                        <div class="field">
                            <label for="f-seo-title-{{ $loc }}">{{ lozan_t('admin.form.seo.metaTitle') }}</label>
                            <input id="f-seo-title-{{ $loc }}" name="seo_title_{{ $loc }}" class="input" dir="{{ $dir }}" maxlength="191"
                                   value="{{ $seoVal('seo_title_'.$loc) }}" placeholder="{{ lozan_t('admin.form.seo.autoPlaceholder') }}"
                                   data-seo-title="{{ $loc }}" data-limit="60">
                            <p class="field__hint muted"><span data-count-for="f-seo-title-{{ $loc }}">0</span>/60 · {{ lozan_t('admin.form.seo.titleHint') }}</p>
                            @error('seo_title_'.$loc)<p class="err">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="f-seo-desc-{{ $loc }}">{{ lozan_t('admin.form.seo.metaDescription') }}</label>
                            <textarea id="f-seo-desc-{{ $loc }}" name="seo_description_{{ $loc }}" class="input" rows="3" dir="{{ $dir }}" maxlength="320"
                                      placeholder="{{ lozan_t('admin.form.seo.autoPlaceholderDesc') }}"
                                      data-seo-desc="{{ $loc }}" data-limit="160">{{ $seoVal('seo_description_'.$loc) }}</textarea>
                            <p class="field__hint muted"><span data-count-for="f-seo-desc-{{ $loc }}">0</span>/160 · {{ lozan_t('admin.form.seo.descHint') }}</p>
                            @error('seo_description_'.$loc)<p class="err">{{ $message }}</p>@enderror
                        </div>
                        <div class="serp" dir="{{ $dir }}" aria-live="polite">
                            <p class="serp__label">{{ lozan_t('admin.form.seo.preview') }}</p>
                            <p class="serp__url" dir="ltr" data-serp-url="{{ $loc }}"></p>
                            <p class="serp__title" data-serp-title="{{ $loc }}"></p>
                            <p class="serp__desc" data-serp-desc="{{ $loc }}"></p>
                        </div>
                    </fieldset>
                @endforeach
            </div>

            <div class="field field--check">
                <input type="hidden" name="seo_noindex" value="0">
                <input id="f-noindex" type="checkbox" name="seo_noindex" value="1" @checked(old('seo_noindex', $product->seo_noindex ?? false))>
                <label for="f-noindex">{{ lozan_t('admin.form.seo.noindex') }}</label>
            </div>
            <p class="field__hint muted">{{ lozan_t('admin.form.seo.noindexHint') }}</p>

            <h3>{{ lozan_t('admin.form.seo.richTitle') }}</h3>
            <p class="muted">{{ lozan_t('admin.form.seo.richHint') }}</p>
            <div class="advanced__grid advanced__grid--compact">
                <div class="field">
                    <label for="f-brand">{{ lozan_t('admin.form.seo.brand') }}</label>
                    <input id="f-brand" name="brand" class="input" maxlength="100" value="{{ $seoVal('brand') }}" placeholder="{{ lozan_t('meta.siteName') }}">
                    @error('brand')<p class="err">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="f-material">{{ lozan_t('admin.form.seo.material') }}</label>
                    <input id="f-material" name="material" class="input" maxlength="100" value="{{ $seoVal('material') }}" placeholder="{{ lozan_t('admin.form.seo.materialPh') }}">
                    @error('material')<p class="err">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="f-sku">{{ lozan_t('admin.form.seo.sku') }}</label>
                    <input id="f-sku" name="sku" class="input" dir="ltr" maxlength="64" value="{{ $seoVal('sku') }}" placeholder="{{ $product ? 'LZ-'.$product->id : lozan_t('admin.form.seo.autoPlaceholder') }}">
                    @error('sku')<p class="err">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="f-gtin">{{ lozan_t('admin.form.seo.gtin') }}</label>
                    <input id="f-gtin" name="gtin" class="input" dir="ltr" inputmode="numeric" maxlength="14" value="{{ $seoVal('gtin') }}">
                    @error('gtin')<p class="err">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="f-mpn">{{ lozan_t('admin.form.seo.mpn') }}</label>
                    <input id="f-mpn" name="mpn" class="input" dir="ltr" maxlength="70" value="{{ $seoVal('mpn') }}">
                    @error('mpn')<p class="err">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
    </details>
    <p><button class="btn btn--gold" type="submit">{{ lozan_t('admin.form.save') }}</button>
    <a class="btn btn--ghost" href="{{ route('admin.products') }}">{{ lozan_t('admin.form.cancel') }}</a></p>
</form>
@endsection

@push('scripts')
<script>
(function () {
    var root = document.querySelector('[data-seo-form]');
    if (!root) return;
    var $ = function (id) { return document.getElementById(id); };
    var clip = function (text, max) { text = (text || '').replace(/\s+/g, ' ').trim(); return text.length > max ? text.slice(0, max - 1) + '…' : text; };
    var slugify = function (text) { return (text || '').toLowerCase().normalize('NFKD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''); };

    function update() {
        var slug = $('f-slug').value.trim() || root.dataset.currentSlug || slugify($('f-name-en').value) || '…';
        ['ar', 'en'].forEach(function (loc) {
            var name = loc === 'en' ? ($('f-name-en').value.trim() || $('f-name').value.trim()) : $('f-name').value.trim();
            var desc = loc === 'en' ? ($('f-description-en').value.trim() || $('f-description').value) : $('f-description').value;
            var title = $('f-seo-title-' + loc).value.trim() || (name + root.dataset['suffix' + (loc === 'ar' ? 'Ar' : 'En')]);
            var metaDesc = $('f-seo-desc-' + loc).value.trim() || clip(desc, 140);
            document.querySelector('[data-serp-url="' + loc + '"]').textContent = root.dataset.baseUrl + (loc === 'en' ? '/en' : '') + '/product/' + slug;
            document.querySelector('[data-serp-title="' + loc + '"]').textContent = clip(title, 60);
            document.querySelector('[data-serp-desc="' + loc + '"]').textContent = clip(metaDesc, 160);
        });
        document.querySelectorAll('[data-count-for]').forEach(function (el) {
            var input = $(el.dataset.countFor);
            var limit = parseInt(input.dataset.limit, 10);
            el.textContent = input.value.length;
            el.classList.toggle('field__over', input.value.length > limit);
        });
    }

    $('f-slug').addEventListener('input', function () {
        this.value = this.value.toLowerCase().replace(/[^a-z0-9-]+/g, '-');
        update();
    });
    ['f-name', 'f-name-en', 'f-description', 'f-description-en', 'f-seo-title-ar', 'f-seo-title-en', 'f-seo-desc-ar', 'f-seo-desc-en'].forEach(function (id) {
        $(id).addEventListener('input', update);
    });
    update();
})();
</script>
@endpush
