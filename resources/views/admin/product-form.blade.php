@extends('layouts.admin')

@section('wide', true)

@php
    $t = fn (string $key, array $replace = []) => lozan_t('admin.form.'.$key, $replace);
    $isRtl = app()->getLocale() === 'ar';
    $images = $product?->images ?? collect();
    $coverImage = $product?->coverImage();
    $keptIds = collect(old('keep_images', $images->pluck('id')->all()))->map(fn ($id) => (int) $id)->all();
    $oldCover = old('cover', $coverImage ? 'existing:'.$coverImage->id : null);

    $colors = old('color_ar')
        ? collect(old('color_ar'))->map(fn ($ar, $i) => ['ar' => $ar, 'en' => old('color_en.'.$i), 'hex' => old('color_hex.'.$i)])->filter(fn ($c) => trim((string) $c['ar']) !== '')->values()
        : ($product?->colors ?? collect())->map(fn ($c) => ['ar' => $c->name_ar, 'en' => $c->name_en, 'hex' => $c->hex]);
    $colorPresets = [
        ['ar' => 'أسود', 'en' => 'Black', 'hex' => '#1a1a1a'],
        ['ar' => 'أبيض', 'en' => 'White', 'hex' => '#ffffff'],
        ['ar' => 'عاجي', 'en' => 'Ivory', 'hex' => '#f4ecd8'],
        ['ar' => 'بيج', 'en' => 'Beige', 'hex' => '#d8c3a5'],
        ['ar' => 'ذهبي', 'en' => 'Gold', 'hex' => '#c5a02d'],
        ['ar' => 'فضي', 'en' => 'Silver', 'hex' => '#bfc1c2'],
        ['ar' => 'وردي', 'en' => 'Pink', 'hex' => '#e8a7b8'],
        ['ar' => 'أحمر', 'en' => 'Red', 'hex' => '#b3202a'],
        ['ar' => 'عنابي', 'en' => 'Burgundy', 'hex' => '#6e1423'],
        ['ar' => 'خمري', 'en' => 'Maroon', 'hex' => '#5c1a1b'],
        ['ar' => 'بنفسجي', 'en' => 'Purple', 'hex' => '#5b3a8c'],
        ['ar' => 'كحلي', 'en' => 'Navy', 'hex' => '#1d2a4d'],
        ['ar' => 'أزرق', 'en' => 'Blue', 'hex' => '#2f5fa7'],
        ['ar' => 'تركوازي', 'en' => 'Turquoise', 'hex' => '#2aa9a4'],
        ['ar' => 'أخضر', 'en' => 'Green', 'hex' => '#2f6b3a'],
        ['ar' => 'بني', 'en' => 'Brown', 'hex' => '#6b4a2b'],
        ['ar' => 'رمادي', 'en' => 'Grey', 'hex' => '#8a8a8a'],
    ];

    $selectedSizes = collect(old('sizes', $product?->sizeValues() ?? []))->map(fn ($s) => (string) $s)->filter()->values()->all();
    $sizeOptions = collect(eu_size_presets())->map(fn ($s) => (string) $s)->merge($selectedSizes)->unique()
        ->sortBy(fn ($s) => is_numeric($s) ? (float) $s : PHP_INT_MAX)->values();

    $published = (bool) old('published', $product->published ?? true);
    $stock = old('stock_status', $product->stock_status ?? 'in_stock');
    $category = old('category', $product->category ?? 'gown');
    $price = old('price', $product->price ?? '');
    $offer = old('offer_price', $product->offer_price ?? '');

    $seoKeys = ['slug', 'seo_title_ar', 'seo_title_en', 'seo_description_ar', 'seo_description_en', 'seo_noindex', 'brand', 'sku', 'gtin', 'mpn', 'material'];
    $advancedOpen = collect($seoKeys)->contains(fn ($k) => $errors->has($k));
    $seoVal = fn (string $k) => old($k, $product->{$k} ?? '');
    $siteSuffix = [
        'ar' => ' | '.lozan_t('meta.siteName', [], 'ar').' — '.lozan_t('meta.geoPlacename', [], 'ar'),
        'en' => ' | '.lozan_t('meta.siteName', [], 'en').' — '.lozan_t('meta.geoPlacename', [], 'en'),
    ];
@endphp

@push('head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=DM+Sans:wght@400;500;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
@endpush

@section('content')
<form id="product-form" class="pf" method="post" enctype="multipart/form-data"
      action="{{ $product ? route('admin.products.update', $product) : route('admin.products.store') }}"
      data-locale="{{ app()->getLocale() }}"
      data-currency="{{ lozan_t('currency.symbol') }}"
      data-label-unavailable="{{ lozan_t('product.unavailableBadge') }}"
      data-label-sale="{{ lozan_t('product.saleBadge', ['pct' => '{pct}']) }}"
      data-label-savings="{{ $t('offerSavings', ['pct' => '{pct}']) }}"
      data-label-offer-high="{{ $t('errOfferTooHigh') }}"
      data-label-cover="{{ $t('cover') }}"
      data-label-set-cover="{{ $t('setCover') }}"
      data-label-remove="{{ $t('removeImage') }}"
      data-label-undo="{{ $t('undoRemove') }}"
      data-label-compressing="{{ $t('compressing') }}"
      data-label-saving="{{ $t('saving') }}"
      data-label-color-exists="{{ $t('colorExists') }}"
      data-label-unsaved="{{ $t('unsaved') }}"
      data-categories="{{ json_encode(['gown' => lozan_t('category.gown'), 'party' => lozan_t('category.party'), 'all' => lozan_t('category.all')], JSON_UNESCAPED_UNICODE) }}">
    @csrf
    @if($product) @method('PUT') @endif

    <header class="pf-head">
        <a class="pf-head__back" href="{{ route('admin.products') }}">{{ $t('back') }}</a>
        <h1 class="pf-head__title">{{ $product ? $t('editTitle') : $t('newTitle') }}</h1>
    </header>

    @if($errors->any())
        <div class="pf-alert" role="alert">
            <p class="pf-alert__title">{{ $t('errorsTitle') }}</p>
            <ul>
                @foreach($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="pf-layout">
        <div class="pf-main">

            {{-- Photos --}}
            <section class="pf-section" aria-labelledby="pf-photos-title">
                <div class="pf-section__head">
                    <h2 id="pf-photos-title">{{ $t('imagesTitle') }}</h2>
                    <p>{{ $t('photosHint') }}</p>
                </div>
                <div class="pf-photos" data-photos>
                    @foreach($images as $img)
                        @php $kept = in_array($img->id, $keptIds, true); @endphp
                        <figure class="pf-photo {{ $kept ? '' : 'is-removed' }}" data-photo="existing:{{ $img->id }}">
                            <img src="{{ $img->url() }}" alt="" loading="lazy">
                            <input type="hidden" name="keep_images[]" value="{{ $img->id }}" @disabled(! $kept)>
                            <label class="pf-photo__cover">
                                <input type="radio" name="cover" value="existing:{{ $img->id }}" @checked($oldCover === 'existing:'.$img->id) @disabled(! $kept)>
                                <span data-on="{{ $t('cover') }}" data-off="{{ $t('setCover') }}"></span>
                            </label>
                            <button type="button" class="pf-photo__remove" data-remove-photo aria-label="{{ $t('removeImage') }}">{{ $kept ? $t('removeImage') : $t('undoRemove') }}</button>
                        </figure>
                    @endforeach
                    <label class="pf-drop" data-drop>
                        <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple data-file-input>
                        <span class="pf-drop__icon" aria-hidden="true">+</span>
                        <span class="pf-drop__text">{{ $t('addPhotos') }}</span>
                        <span class="pf-drop__hint">{{ $t('photosFormats') }}</span>
                    </label>
                </div>
                <p class="pf-status" data-photo-status aria-live="polite" hidden></p>
                @error('images')<p class="pf-error">{{ $message }}</p>@enderror
                @error('images.*')<p class="pf-error">{{ $message }}</p>@enderror
            </section>

            {{-- Details --}}
            <section class="pf-section" aria-labelledby="pf-details-title">
                <div class="pf-section__head">
                    <h2 id="pf-details-title">{{ $t('detailsTitle') }}</h2>
                </div>
                <div class="pf-grid">
                    <div class="pf-field">
                        <label for="f-name">{{ $t('nameAr') }}</label>
                        <input id="f-name" name="name" class="pf-input" dir="rtl" required minlength="2" maxlength="191" value="{{ old('name', $product->name ?? '') }}">
                        @error('name')<p class="pf-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="pf-field">
                        <label for="f-name-en">{{ $t('nameEn') }}</label>
                        <input id="f-name-en" name="name_en" class="pf-input" dir="ltr" maxlength="191" value="{{ old('name_en', $product->name_en ?? '') }}">
                        @error('name_en')<p class="pf-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="pf-field">
                        <label for="f-description">{{ $t('description') }}</label>
                        <textarea id="f-description" name="description" class="pf-input" dir="rtl" rows="5" placeholder="{{ $t('descriptionPh') }}">{{ old('description', $product->description ?? '') }}</textarea>
                    </div>
                    <div class="pf-field">
                        <label for="f-description-en">{{ $t('descriptionEn') }}</label>
                        <textarea id="f-description-en" name="description_en" class="pf-input" dir="ltr" rows="5" placeholder="{{ $t('descriptionEnPh') }}">{{ old('description_en', $product->description_en ?? '') }}</textarea>
                        <p class="pf-hint">{{ $t('descriptionEnHint') }}</p>
                    </div>
                </div>
                <fieldset class="pf-field pf-fieldset">
                    <legend>{{ $t('category') }}</legend>
                    <div class="pf-segmented">
                        @foreach(['gown' => 'catGown', 'party' => 'catParty', 'all' => 'catAll'] as $value => $label)
                            <label class="pf-segmented__option">
                                <input type="radio" name="category" value="{{ $value }}" @checked($category === $value)>
                                <span>{{ $t($label) }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </section>

            {{-- Price --}}
            <section class="pf-section" aria-labelledby="pf-price-title">
                <div class="pf-section__head">
                    <h2 id="pf-price-title">{{ $t('priceTitle') }}</h2>
                </div>
                <div class="pf-grid">
                    <div class="pf-field">
                        <label for="f-price">{{ $t('price', ['currency' => 'KWD']) }}</label>
                        <div class="pf-money">
                            <input id="f-price" name="price" type="number" step="0.001" min="0" inputmode="decimal" class="pf-input" dir="ltr" required value="{{ $price }}">
                            <span class="pf-money__unit">KWD</span>
                        </div>
                        @error('price')<p class="pf-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="pf-field">
                        <label for="f-offer">{{ $t('offerPrice', ['currency' => 'KWD']) }}</label>
                        <div class="pf-money">
                            <input id="f-offer" name="offer_price" type="number" step="0.001" min="0" inputmode="decimal" class="pf-input" dir="ltr" value="{{ $offer }}" placeholder="{{ $t('offerPricePh') }}">
                            <span class="pf-money__unit">KWD</span>
                        </div>
                        <p class="pf-hint" data-offer-note>{{ $t('offerPriceHint') }}</p>
                        @error('offer_price')<p class="pf-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            {{-- Colors --}}
            <section class="pf-section" aria-labelledby="pf-colors-title">
                <div class="pf-section__head">
                    <h2 id="pf-colors-title">{{ $t('colorsTitle') }}</h2>
                    <p>{{ $t('colorsHintShort') }}</p>
                </div>
                <div class="pf-presets" role="group" aria-label="{{ $t('presetTitle') }}">
                    @foreach($colorPresets as $preset)
                        <button type="button" class="pf-preset" data-color-preset="{{ json_encode($preset, JSON_UNESCAPED_UNICODE) }}">
                            <span class="pf-swatch" style="--swatch: {{ $preset['hex'] }}"></span>
                            <span>{{ $isRtl ? $preset['ar'] : $preset['en'] }}</span>
                        </button>
                    @endforeach
                </div>
                <ul class="pf-colors" data-colors>
                    @foreach($colors as $c)
                        <li class="pf-color" data-color-row>
                            <label class="pf-color__pick">
                                <span class="visually-hidden">{{ $t('pickColor') }}</span>
                                <input type="color" name="color_hex[]" value="{{ preg_match('/^#[0-9a-f]{6}$/i', (string) $c['hex']) ? $c['hex'] : '#888888' }}">
                            </label>
                            <input name="color_ar[]" class="pf-input" dir="rtl" required value="{{ $c['ar'] }}" placeholder="{{ $t('colorArPh') }}" aria-label="{{ $t('colorArPh') }}">
                            <input name="color_en[]" class="pf-input" dir="ltr" value="{{ $c['en'] }}" placeholder="{{ $t('colorEnPh') }}" aria-label="{{ $t('colorEnPh') }}">
                            <button type="button" class="pf-icon-btn" data-remove-color aria-label="{{ $t('removeColor') }}">×</button>
                        </li>
                    @endforeach
                </ul>
                <template data-color-template>
                    <li class="pf-color" data-color-row>
                        <label class="pf-color__pick">
                            <span class="visually-hidden">{{ $t('pickColor') }}</span>
                            <input type="color" name="color_hex[]" value="#888888">
                        </label>
                        <input name="color_ar[]" class="pf-input" dir="rtl" required placeholder="{{ $t('colorArPh') }}" aria-label="{{ $t('colorArPh') }}">
                        <input name="color_en[]" class="pf-input" dir="ltr" placeholder="{{ $t('colorEnPh') }}" aria-label="{{ $t('colorEnPh') }}">
                        <button type="button" class="pf-icon-btn" data-remove-color aria-label="{{ $t('removeColor') }}">×</button>
                    </li>
                </template>
                <button type="button" class="pf-link-btn" data-add-color>{{ $t('addCustom') }}</button>
                <p class="pf-status" data-color-status aria-live="polite" hidden></p>
            </section>

            {{-- Sizes --}}
            <section class="pf-section" aria-labelledby="pf-sizes-title">
                <div class="pf-section__head">
                    <h2 id="pf-sizes-title">{{ $t('sizesTitle') }}</h2>
                    <p>{{ $t('sizesHint') }}</p>
                </div>
                <div class="pf-sizes" data-sizes>
                    @foreach($sizeOptions as $size)
                        <label class="pf-chip">
                            <input type="checkbox" name="sizes[]" value="{{ $size }}" @checked(in_array($size, $selectedSizes, true))>
                            <span dir="ltr">EU {{ $size }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="pf-inline-add">
                    <label for="f-custom-size" class="visually-hidden">{{ $t('customSize') }}</label>
                    <input id="f-custom-size" class="pf-input" dir="ltr" maxlength="10" placeholder="{{ $t('sizePh') }}" data-custom-size>
                    <button type="button" class="pf-link-btn" data-add-size>{{ $t('addSize') }}</button>
                </div>
            </section>

            {{-- Advanced: SEO & rich results --}}
            <details class="pf-advanced" @if($advancedOpen) open @endif>
                <summary>
                    <span class="pf-advanced__title">{{ lozan_t('admin.form.seo.title') }}</span>
                    <span class="pf-advanced__hint">{{ lozan_t('admin.form.seo.summaryHint') }}</span>
                </summary>

                <div class="pf-advanced__body" data-seo-form
                     data-base-url="{{ url('/') }}"
                     data-suffix-ar="{{ $siteSuffix['ar'] }}"
                     data-suffix-en="{{ $siteSuffix['en'] }}"
                     data-current-slug="{{ $product->slug ?? '' }}">

                    <div class="pf-section__head">
                        <h3>{{ lozan_t('admin.form.seo.searchTitle') }}</h3>
                        <p>{{ lozan_t('admin.form.seo.searchHint') }}</p>
                    </div>

                    <div class="pf-field">
                        <label for="f-slug">{{ lozan_t('admin.form.seo.slug') }}</label>
                        <div class="pf-slug" dir="ltr">
                            <span class="pf-slug__base">{{ url('/product') }}/</span>
                            <input id="f-slug" name="slug" class="pf-input" dir="ltr" value="{{ $seoVal('slug') }}" placeholder="{{ lozan_t('admin.form.seo.slugAuto') }}" pattern="[a-z0-9-]*" maxlength="191">
                        </div>
                        <p class="pf-hint">{{ $product ? lozan_t('admin.form.seo.slugHintEdit') : lozan_t('admin.form.seo.slugHintNew') }}</p>
                        @error('slug')<p class="pf-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="pf-grid">
                        @foreach(['ar' => 'rtl', 'en' => 'ltr'] as $loc => $dir)
                            <fieldset class="pf-lang">
                                <legend>{{ lozan_t('admin.form.seo.lang'.ucfirst($loc)) }}</legend>
                                <div class="pf-field">
                                    <label for="f-seo-title-{{ $loc }}">{{ lozan_t('admin.form.seo.metaTitle') }}</label>
                                    <input id="f-seo-title-{{ $loc }}" name="seo_title_{{ $loc }}" class="pf-input" dir="{{ $dir }}" maxlength="191"
                                           value="{{ $seoVal('seo_title_'.$loc) }}" placeholder="{{ lozan_t('admin.form.seo.autoPlaceholder') }}" data-limit="60">
                                    <p class="pf-hint"><span data-count-for="f-seo-title-{{ $loc }}">0</span>/60 · {{ lozan_t('admin.form.seo.titleHint') }}</p>
                                    @error('seo_title_'.$loc)<p class="pf-error">{{ $message }}</p>@enderror
                                </div>
                                <div class="pf-field">
                                    <label for="f-seo-desc-{{ $loc }}">{{ lozan_t('admin.form.seo.metaDescription') }}</label>
                                    <textarea id="f-seo-desc-{{ $loc }}" name="seo_description_{{ $loc }}" class="pf-input" rows="3" dir="{{ $dir }}" maxlength="320"
                                              placeholder="{{ lozan_t('admin.form.seo.autoPlaceholderDesc') }}" data-limit="160">{{ $seoVal('seo_description_'.$loc) }}</textarea>
                                    <p class="pf-hint"><span data-count-for="f-seo-desc-{{ $loc }}">0</span>/160 · {{ lozan_t('admin.form.seo.descHint') }}</p>
                                    @error('seo_description_'.$loc)<p class="pf-error">{{ $message }}</p>@enderror
                                </div>
                                <div class="pf-serp" dir="{{ $dir }}" aria-live="polite">
                                    <p class="pf-serp__label">{{ lozan_t('admin.form.seo.preview') }}</p>
                                    <p class="pf-serp__url" dir="ltr" data-serp-url="{{ $loc }}"></p>
                                    <p class="pf-serp__title" data-serp-title="{{ $loc }}"></p>
                                    <p class="pf-serp__desc" data-serp-desc="{{ $loc }}"></p>
                                </div>
                            </fieldset>
                        @endforeach
                    </div>

                    <label class="pf-check">
                        <input type="hidden" name="seo_noindex" value="0">
                        <input type="checkbox" name="seo_noindex" value="1" @checked(old('seo_noindex', $product->seo_noindex ?? false))>
                        <span>
                            <strong>{{ lozan_t('admin.form.seo.noindex') }}</strong>
                            <small>{{ lozan_t('admin.form.seo.noindexHint') }}</small>
                        </span>
                    </label>

                    <div class="pf-section__head">
                        <h3>{{ lozan_t('admin.form.seo.richTitle') }}</h3>
                        <p>{{ lozan_t('admin.form.seo.richHint') }}</p>
                    </div>
                    <div class="pf-grid pf-grid--3">
                        @foreach([
                            ['brand', 'brand', lozan_t('meta.siteName'), 'auto', 100],
                            ['material', 'material', lozan_t('admin.form.seo.materialPh'), 'auto', 100],
                            ['sku', 'sku', $product ? 'LZ-'.$product->id : lozan_t('admin.form.seo.autoPlaceholder'), 'ltr', 64],
                            ['gtin', 'gtin', '', 'ltr', 14],
                            ['mpn', 'mpn', '', 'ltr', 70],
                        ] as [$field, $label, $placeholder, $dir, $max])
                            <div class="pf-field">
                                <label for="f-{{ $field }}">{{ lozan_t('admin.form.seo.'.$label) }}</label>
                                <input id="f-{{ $field }}" name="{{ $field }}" class="pf-input" dir="{{ $dir }}" maxlength="{{ $max }}" value="{{ $seoVal($field) }}" placeholder="{{ $placeholder }}" @if($field === 'gtin') inputmode="numeric" @endif>
                                @error($field)<p class="pf-error">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            </details>
        </div>

        {{-- Side panel --}}
        <aside class="pf-side">
            <div class="pf-side__sticky">
                <section class="pf-panel">
                    <label class="pf-switch">
                        <input type="checkbox" name="published" value="1" @checked($published) data-published>
                        <span class="pf-switch__track" aria-hidden="true"></span>
                        <span class="pf-switch__text">
                            <strong>{{ $t('published') }}</strong>
                            <small data-published-hint data-on="{{ $t('visibleHint') }}" data-off="{{ $t('hiddenHint') }}">{{ $published ? $t('visibleHint') : $t('hiddenHint') }}</small>
                        </span>
                    </label>
                    @if($product && $product->published)
                        <a class="pf-panel__link" href="{{ product_url($product) }}" target="_blank" rel="noopener">{{ $t('viewInStore') }}</a>
                    @endif
                </section>

                <fieldset class="pf-panel pf-stock">
                    <legend>{{ $t('stockStatus') }}</legend>
                    @foreach(['in_stock' => 'InStock', 'backorder' => 'Backorder', 'unavailable' => 'Unavailable'] as $value => $key)
                        <label class="pf-stock__option">
                            <input type="radio" name="stock_status" value="{{ $value }}" @checked($stock === $value)>
                            <span>
                                <strong>{{ $t('stock'.$key) }}</strong>
                                <small>{{ $t('stock'.$key.'Hint') }}</small>
                            </span>
                        </label>
                    @endforeach
                </fieldset>

                <section class="pf-preview" aria-label="{{ $t('previewTitle') }}">
                    <p class="pf-preview__label">{{ $t('previewTitle') }}</p>
                    <article class="card pf-preview__card" data-preview>
                        <div class="card__media">
                            <img class="card__img" alt="" data-preview-img @if($coverImage) src="{{ $coverImage->url() }}" @else hidden @endif>
                            <div class="card__ph" data-preview-ph @if($coverImage) hidden @endif></div>
                            <span class="card__badge" data-preview-badge hidden></span>
                        </div>
                        <div class="card__body">
                            <h3 class="card__name" data-preview-name data-placeholder="{{ $t('previewName') }}">{{ $product ? product_display_name($product) : $t('previewName') }}</h3>
                            <p class="card__cat" data-preview-cat></p>
                            <p class="card__price" data-preview-price></p>
                        </div>
                    </article>
                    <p class="pf-preview__hidden" data-preview-hidden @if($published) hidden @endif>{{ $t('previewHidden') }}</p>
                </section>

                <div class="pf-actions">
                    <button class="pf-save" type="submit" data-save>{{ $product ? $t('saveChanges') : $t('createProduct') }}</button>
                    <a class="pf-cancel" href="{{ route('admin.products') }}">{{ $t('cancel') }}</a>
                </div>
            </div>
        </aside>
    </div>

    <div class="pf-savebar">
        <button class="pf-save" type="submit" data-save>{{ $product ? $t('saveChanges') : $t('createProduct') }}</button>
    </div>
</form>
@endsection

@push('scripts')
    <script src="/js/admin-product-form.js?v={{ filemtime(public_path('js/admin-product-form.js')) }}" defer></script>
@endpush
