<article class="card {{ !$product->isInStock() ? 'card--dim' : '' }}">
    <a href="{{ product_url($product) }}" class="card__media">
        @if($product->coverUrl())
            <img src="{{ $product->coverUrl() }}" alt="{{ product_display_name($product) }}" class="card__img" loading="lazy" width="400" height="520">
        @else
            <div class="card__ph" aria-hidden="true"></div>
        @endif
        @if(!$product->isInStock())
            <span class="card__badge card__badge--unavail">{{ lozan_t('product.unavailableBadge') }}</span>
        @elseif(is_on_sale($product))
            <span class="card__badge">−{{ discount_percent($product) }}%</span>
        @endif
    </a>
    <div class="card__body">
        <h3 class="card__name">
            <a href="{{ product_url($product) }}">{{ product_display_name($product) }}</a>
        </h3>
        @if($product->category)
            <p class="card__cat">{{ lozan_t('category.'.$product->category) }}</p>
        @endif
        <p class="card__price">
            @if(is_on_sale($product) && $product->isInStock())
                <span class="card__price-sale">{{ format_kwd($product->offer_price) }}</span>
                <span class="card__price-reg">{{ format_kwd($product->price) }}</span>
            @else
                {{ format_kwd($product->price) }}
            @endif
        </p>
    </div>
</article>
