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
        $name = product_display_name($product);
        $alt = $product->name_en && $product->name_en !== $product->name
            ? ($name === $product->name ? $product->name_en : $product->name)
            : lozan_t('meta.siteNameAlt');
        $site = lozan_t('meta.siteName');
        $place = lozan_t('meta.geoPlacename');
        $desc = trim((string) $product->description);
        $base = $desc !== '' ? mb_substr($desc, 0, 140).(mb_strlen($desc) > 140 ? '…' : '') : lozan_t('meta.productFallbackDesc');
        if (! str_contains($base, $place)) {
            $base .= ' — '.$place.'.';
        }
        $path = 'product/'.$product->slug;
        $gallery = $product->galleryUrls();

        return [
            'title' => $name.' | '.$site.' — '.$place,
            'description' => $base,
            'keywords' => implode(', ', array_filter([$name, lozan_t('category.'.$product->category), lozan_t('meta.productKeywordSuffix')])),
            'canonical' => locale_url($path),
            'hreflang_ar' => locale_url($path, 'ar'),
            'hreflang_en' => locale_url($path, 'en'),
            'og_type' => 'product',
            'image' => $gallery[0] ?? url('/logo.png'),
            'image_alt' => $name.' — '.lozan_t('meta.productImageAltSuffix'),
            'robots' => $product->published ? 'index,follow' : 'noindex,nofollow',
            'product' => $product,
            'gallery' => $gallery,
            'name_alt' => $alt,
        ];
    }

    public function productJsonLd(Product $product, array $seo): array
    {
        $loc = lozan_location();
        $locale = app()->getLocale();
        $url = $seo['canonical'];
        $onSale = is_on_sale($product);
        $headline = effective_price($product);
        $reg = (float) $product->price;
        $validUntil = now()->addYear()->format('Y-m-d');
        $googleCat = $product->category === 'party'
            ? 'Apparel & Accessories > Clothing > Dresses'
            : 'Apparel & Accessories > Clothing > Dresses';
        $colorStr = collect($product->colorArrays())
            ->map(fn ($c) => color_label($c))
            ->filter()
            ->implode(', ');
        $availability = $product->isUnavailable()
            ? 'https://schema.org/OutOfStock'
            : 'https://schema.org/InStock';
        $priceSpecs = [[
            '@type' => 'UnitPriceSpecification',
            'price' => number_format($headline, 3, '.', ''),
            'priceCurrency' => $loc['currency'],
            'priceType' => $onSale ? 'https://schema.org/SalePrice' : 'https://schema.org/ListPrice',
            'valueAddedTaxIncluded' => true,
        ]];
        if ($onSale) {
            $priceSpecs[] = [
                '@type' => 'UnitPriceSpecification',
                'price' => number_format($reg, 3, '.', ''),
                'priceCurrency' => $loc['currency'],
                'priceType' => 'https://schema.org/ListPrice',
                'valueAddedTaxIncluded' => true,
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            '@id' => $url.'#product',
            'name' => product_display_name($product),
            'alternateName' => $seo['name_alt'] ?? null,
            'description' => $product->description ?: lozan_t('meta.productFallbackDesc'),
            'inLanguage' => $locale,
            'image' => $seo['gallery'] ?: null,
            'sku' => (string) $product->id,
            'url' => $url,
            'category' => $googleCat,
            'additionalType' => 'https://schema.org/Dress',
            'color' => $colorStr ?: null,
            'brand' => [
                '@type' => 'Brand',
                'name' => lozan_t('meta.siteName'),
                'alternateName' => lozan_t('meta.siteNameAlt'),
                'logo' => url('/logo.png'),
                'url' => locale_url('/'),
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => $url,
                'priceCurrency' => $loc['currency'],
                'price' => number_format($headline, 3, '.', ''),
                'priceSpecification' => $priceSpecs,
                'priceValidUntil' => $validUntil,
                'availability' => $availability,
                'itemCondition' => 'https://schema.org/NewCondition',
                'shippingDetails' => [
                    '@type' => 'OfferShippingDetails',
                    'shippingRate' => [
                        '@type' => 'MonetaryAmount',
                        'value' => config('lozan.shipping_fee'),
                        'currency' => $loc['currency'],
                    ],
                    'shippingDestination' => [
                        '@type' => 'DefinedRegion',
                        'addressCountry' => $loc['countryCode'],
                    ],
                ],
                'hasMerchantReturnPolicy' => [
                    '@type' => 'MerchantReturnPolicy',
                    'applicableCountry' => $loc['countryCode'],
                    'merchantReturnDays' => config('lozan.return_days'),
                    'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
                ],
            ],
        ];
    }
}
