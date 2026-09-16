<?php

namespace App\Services;

use App\Models\Product;

class SeoService
{
    public function page(string $routeName, string $path): array
    {
        $map = [
            'home' => ['desc' => 'meta.defaultDescription', 'title' => null],
            'shop' => ['title' => 'meta.titleShop', 'desc' => 'meta.descShop'],
            'cart' => ['title' => 'meta.titleCart', 'desc' => 'meta.descCart', 'robots' => 'noindex,nofollow'],
            'checkout' => ['title' => 'meta.titleCheckout', 'desc' => 'meta.descCheckout', 'robots' => 'noindex,nofollow'],
            'thanks' => ['title' => 'meta.titleThanks', 'desc' => 'meta.descThanks', 'robots' => 'noindex,nofollow'],
            'not-found' => ['title' => 'meta.titleNotFound', 'desc' => 'meta.descNotFound', 'robots' => 'noindex'],
        ];
        $c = $map[$routeName] ?? ['title' => null, 'desc' => 'meta.defaultDescription'];
        $site = lozan_t('meta.siteName');
        $full = ! empty($c['title']) ? lozan_t($c['title']).' | '.$site : lozan_t('meta.defaultFullTitle');

        return [
            'title' => $full,
            'description' => lozan_t($c['desc']),
            'keywords' => lozan_t('meta.keywords'),
            'canonical' => locale_url($path),
            'hreflang_ar' => locale_url($path, 'ar'),
            'hreflang_en' => locale_url($path, 'en'),
            'og_type' => 'website',
            'image' => url('/logo.png'),
            'robots' => $c['robots'] ?? 'index,follow',
        ];
    }

    public function product(Product $product): array
    {
        $locale = app()->getLocale() === 'en' ? 'en' : 'ar';
        $name = product_display_name($product);
        $alt = $product->name_en && $product->name_en !== $product->name
            ? ($name === $product->name ? $product->name_en : $product->name)
            : lozan_t('meta.siteNameAlt');
        $site = lozan_t('meta.siteName');
        $place = lozan_t('meta.geoPlacename');

        $title = trim((string) $product->{'seo_title_'.$locale}) ?: $name.' | '.$site.' — '.$place;
        $description = trim((string) $product->{'seo_description_'.$locale});
        if ($description === '') {
            $desc = trim(preg_replace('/\s+/u', ' ', $product->localizedDescription($locale)));
            $description = $desc !== '' ? mb_substr($desc, 0, 140).(mb_strlen($desc) > 140 ? '…' : '') : lozan_t('meta.productFallbackDesc');
            if (! str_contains($description, $place)) {
                $description .= ' — '.$place.'.';
            }
        }

        $path = 'product/'.$product->slug;
        $gallery = $product->galleryUrls();
        $cover = $product->coverUrl();
        $indexable = $product->published && ! $product->seo_noindex;

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => implode(', ', array_filter([$name, lozan_t('category.'.$product->category), lozan_t('meta.productKeywordSuffix')])),
            'canonical' => locale_url($path),
            'hreflang_ar' => locale_url($path, 'ar'),
            'hreflang_en' => locale_url($path, 'en'),
            'og_type' => 'product',
            'image' => $cover ?? $gallery[0] ?? url('/logo.png'),
            'image_alt' => $name.' — '.lozan_t('meta.productImageAltSuffix'),
            'price' => number_format(effective_price($product), 3, '.', ''),
            'currency' => lozan_location()['currency'],
            'robots' => $indexable ? 'index,follow,max-image-preview:large' : 'noindex,nofollow',
            'product' => $product,
            'gallery' => $cover ? array_values(array_unique([$cover, ...$gallery])) : $gallery,
            'name_alt' => $alt,
        ];
    }

    /**
     * Product + BreadcrumbList structured data, shaped for Google's product
     * snippet and merchant listing rich results.
     */
    public function productJsonLd(Product $product, array $seo): array
    {
        $loc = lozan_location();
        $url = $seo['canonical'];
        $currency = $loc['currency'];
        $onSale = is_on_sale($product);
        $price = number_format(effective_price($product), 3, '.', '');
        $site = lozan_t('meta.siteName');
        $colors = collect($product->colorArrays())->map(fn ($c) => color_label($c))->filter()->implode(', ');

        $offer = array_filter([
            '@type' => 'Offer',
            'url' => $url,
            'priceCurrency' => $currency,
            'price' => $price,
            'priceValidUntil' => $onSale ? now()->addMonths(3)->format('Y-m-d') : null,
            'priceSpecification' => $onSale ? [
                '@type' => 'UnitPriceSpecification',
                'price' => number_format((float) $product->price, 3, '.', ''),
                'priceCurrency' => $currency,
                'priceType' => 'https://schema.org/StrikethroughPrice',
            ] : null,
            'availability' => match ($product->stock_status) {
                'in_stock' => 'https://schema.org/InStock',
                'backorder' => 'https://schema.org/BackOrder',
                default => 'https://schema.org/OutOfStock',
            },
            'itemCondition' => 'https://schema.org/NewCondition',
            'seller' => ['@type' => 'Organization', 'name' => $site, 'url' => locale_url('/')],
            'shippingDetails' => [
                '@type' => 'OfferShippingDetails',
                'shippingRate' => ['@type' => 'MonetaryAmount', 'value' => config('lozan.shipping_fee'), 'currency' => $currency],
                'shippingDestination' => ['@type' => 'DefinedRegion', 'addressCountry' => $loc['countryCode']],
                'deliveryTime' => [
                    '@type' => 'ShippingDeliveryTime',
                    'handlingTime' => ['@type' => 'QuantitativeValue', 'minValue' => config('lozan.handling_min_days'), 'maxValue' => config('lozan.handling_max_days'), 'unitCode' => 'DAY'],
                    'transitTime' => ['@type' => 'QuantitativeValue', 'minValue' => config('lozan.transit_min_days'), 'maxValue' => config('lozan.transit_max_days'), 'unitCode' => 'DAY'],
                ],
            ],
            'hasMerchantReturnPolicy' => [
                '@type' => 'MerchantReturnPolicy',
                'applicableCountry' => $loc['countryCode'],
                'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
                'merchantReturnDays' => config('lozan.return_days'),
            ],
        ], fn ($v) => $v !== null);

        $productNode = array_filter([
            '@type' => 'Product',
            '@id' => $url.'#product',
            'name' => product_display_name($product),
            'alternateName' => $seo['name_alt'] ?? null,
            'description' => trim($product->localizedDescription()) ?: $seo['description'],
            'url' => $url,
            'image' => $seo['gallery'] ?: [url('/logo.png')],
            'sku' => $product->sku ?: 'LZ-'.$product->id,
            'mpn' => $product->mpn,
            'gtin' => $product->gtin,
            'brand' => ['@type' => 'Brand', 'name' => $product->brand ?: $site],
            'category' => 'Apparel & Accessories > Clothing > Dresses',
            'color' => $colors ?: null,
            'material' => $product->material,
            'audience' => ['@type' => 'PeopleAudience', 'suggestedGender' => 'female'],
            'offers' => $offer,
        ], fn ($v) => $v !== null && $v !== '');

        $breadcrumb = [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => lozan_t('nav.home'), 'item' => locale_url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => lozan_t('nav.shop'), 'item' => locale_url('shop')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => product_display_name($product), 'item' => $url],
            ],
        ];

        return ['@context' => 'https://schema.org', '@graph' => [$productNode, $breadcrumb]];
    }
}
