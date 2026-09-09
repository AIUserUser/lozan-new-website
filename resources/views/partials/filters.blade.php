<form method="get" action="{{ $formAction }}" class="filter-form">
    @if(!empty($showCategory) && $showCategory)
        <div class="filters filters--cat" role="tablist" aria-label="{{ lozan_t('shop.filterA11y') }}">
            @foreach(['all' => lozan_t('shop.filterAll'), 'gown' => lozan_t('shop.filterGown'), 'party' => lozan_t('shop.filterParty')] as $val => $label)
                <a href="{{ $formAction }}?category={{ $val }}{{ $selectedColor ? '&color='.urlencode($selectedColor) : '' }}{{ $selectedSize ? '&size='.urlencode($selectedSize) : '' }}"
                   class="filters__btn {{ ($category ?? 'all') === $val ? 'filters__btn--on' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
    @endif
    @if(($colorOptions ?? []) || ($sizeOptions ?? []))
        <div class="filter-inline">
            @if(!empty($colorOptions))
                <label class="visually-hidden" for="color">{{ lozan_t('shop.filterColor') }}</label>
                <select name="color" id="color" class="input filter-select" onchange="this.form.submit()">
                    <option value="">{{ lozan_t('shop.filterColor') }}</option>
                    @foreach($colorOptions as $opt)
                        <option value="{{ $opt['key'] }}" @selected(($selectedColor ?? '') === $opt['key'])>{{ $opt['label'] }}</option>
                    @endforeach
                </select>
            @endif
            @if(!empty($sizeOptions))
                <label class="visually-hidden" for="size">{{ lozan_t('shop.filterSize') }}</label>
                <select name="size" id="size" class="input filter-select" onchange="this.form.submit()">
                    <option value="">{{ lozan_t('shop.filterSize') }}</option>
                    @foreach($sizeOptions as $sz)
                        <option value="{{ $sz }}" @selected(($selectedSize ?? '') === $sz)>{{ format_eu_size($sz) }}</option>
                    @endforeach
                </select>
            @endif
            @if(!empty($showCategory) && $showCategory)
                <input type="hidden" name="category" value="{{ $category ?? 'all' }}">
            @endif
            @if(($selectedColor ?? false) || ($selectedSize ?? false))
                <a class="muted" href="{{ $formAction }}{{ !empty($showCategory) && ($category ?? 'all') !== 'all' ? '?category='.$category : '' }}">{{ lozan_t('shop.filterClear') }}</a>
            @endif
        </div>
    @endif
</form>
