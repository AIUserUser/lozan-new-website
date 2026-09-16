@php
    $showCategory = ! empty($showCategory);
    $category = $showCategory ? ($category ?? 'all') : 'all';
    $colors = $facets['colors'] ?? [];
    $sizes = $facets['sizes'] ?? [];
    $size = $selectedSize ? trim(preg_replace('/^eu\s*/i', '', $selectedSize)) : null;
    $activeColor = collect($colors)->firstWhere('key', $selectedColor);
    $countLabel = fn (int $n) => $n === 1 ? lozan_t('shop.resultCountOne') : lozan_t('shop.resultCount', ['count' => $n]);

    // Build a filter URL from the current state plus overrides (null removes a param).
    $filterUrl = function (array $overrides = []) use ($formAction, $showCategory, $category, $selectedColor, $size) {
        $query = array_filter(array_merge([
            'category' => $showCategory && $category !== 'all' ? $category : null,
            'color' => $selectedColor ?: null,
            'size' => $size ?: null,
        ], $overrides), fn ($v) => $v !== null && $v !== '' && $v !== 'all');

        return $formAction.($query ? '?'.http_build_query($query) : '');
    };
@endphp

<div class="sf" data-shop-filters>
    <div class="sf__bar">
        @if($showCategory)
            <nav class="sf-seg" aria-label="{{ lozan_t('shop.filterA11y') }}">
                @foreach(['all' => 'filterAll', 'gown' => 'filterGown', 'party' => 'filterParty'] as $value => $label)
                    <a href="{{ $filterUrl(['category' => $value]) }}" rel="nofollow" @if($category === $value) aria-current="page" @endif>{{ lozan_t('shop.'.$label) }}</a>
                @endforeach
            </nav>
        @endif

        @if($colors)
            <details class="sf-drop" data-sf-drop>
                <summary class="sf-drop__btn {{ $activeColor ? 'is-active' : '' }}">
                    @if($activeColor)
                        <span class="sf-dot" style="--dot: {{ $activeColor['hex'] ?? 'transparent' }}" aria-hidden="true"></span>
                        <span>{{ $activeColor['label'] }}</span>
                    @else
                        <span>{{ lozan_t('shop.filterColor') }}</span>
                    @endif
                </summary>
                <div class="sf-drop__panel">
                    <ul class="sf-colors" aria-label="{{ lozan_t('shop.filterColorsA11y') }}">
                        @foreach($colors as $opt)
                            @php $on = $selectedColor === $opt['key']; @endphp
                            <li>
                                <a href="{{ $filterUrl(['color' => $on ? null : $opt['key']]) }}" rel="nofollow" class="sf-color {{ $on ? 'is-on' : '' }}" @if($on) aria-current="true" @endif>
                                    <span class="sf-dot" style="--dot: {{ $opt['hex'] ?? 'transparent' }}" aria-hidden="true"></span>
                                    <span class="sf-color__name">{{ $opt['label'] }}</span>
                                    <span class="sf-count">{{ $opt['count'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </details>
        @endif

        @if($sizes)
            <details class="sf-drop" data-sf-drop>
                <summary class="sf-drop__btn {{ $size ? 'is-active' : '' }}">
                    <span dir="ltr">{{ $size ? format_eu_size($size) : lozan_t('shop.filterSize') }}</span>
                </summary>
                <div class="sf-drop__panel">
                    <ul class="sf-sizes" aria-label="{{ lozan_t('shop.filterSizesA11y') }}">
                        @foreach($sizes as $opt)
                            @php $on = $size === $opt['value']; @endphp
                            <li>
                                <a href="{{ $filterUrl(['size' => $on ? null : $opt['value']]) }}" rel="nofollow" class="sf-size {{ $on ? 'is-on' : '' }}" @if($on) aria-current="true" @endif
                                   title="{{ $countLabel($opt['count']) }}">
                                    <span dir="ltr">{{ $opt['value'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </details>
        @endif

        @isset($resultCount)
            <p class="sf__count" aria-live="polite">{{ $countLabel($resultCount) }}</p>
        @endisset
    </div>

    @if($activeColor || $size)
        <div class="sf__active">
            @if($activeColor)
                <a class="sf-chip" href="{{ $filterUrl(['color' => null]) }}" rel="nofollow" aria-label="{{ lozan_t('shop.removeFilter', ['name' => $activeColor['label']]) }}">
                    <span class="sf-dot sf-dot--sm" style="--dot: {{ $activeColor['hex'] ?? 'transparent' }}" aria-hidden="true"></span>
                    {{ $activeColor['label'] }}
                    <span class="sf-chip__x" aria-hidden="true">×</span>
                </a>
            @endif
            @if($size)
                <a class="sf-chip" href="{{ $filterUrl(['size' => null]) }}" rel="nofollow" aria-label="{{ lozan_t('shop.removeFilter', ['name' => format_eu_size($size)]) }}">
                    <span dir="ltr">{{ format_eu_size($size) }}</span>
                    <span class="sf-chip__x" aria-hidden="true">×</span>
                </a>
            @endif
            <a class="sf__clear" href="{{ $filterUrl(['color' => null, 'size' => null]) }}" rel="nofollow">{{ lozan_t('shop.filterClear') }}</a>
        </div>
    @endif
</div>
