<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Builds /llms.txt and /llms-full.txt (https://llmstxt.org): a Markdown guide
 * to the store for AI assistants, generated from the live catalogue.
 */
class LlmsTxtService
{
    public function index(): string
    {
        $products = $this->products();

        $lines = [
            ...$this->header(),
            '## Shop',
            '',
            '- [Shop all dresses (English)]('.locale_url('shop', 'en').'): Full collection of gowns and party wear with prices, colors and sizes.',
            '- [المتجر — Shop (Arabic)]('.locale_url('shop', 'ar').'): The same collection in Arabic, the store\'s primary language.',
            '- [Home (English)]('.locale_url('', 'en').'): Featured pieces and store introduction.',
            '',
            '## Products',
            '',
        ];
        foreach ($products as $p) {
            $lines[] = '- ['.$this->name($p).']('.product_url($p, 'en').'): '.$this->summary($p);
        }
        if ($products->isEmpty()) {
            $lines[] = '- No products are listed right now.';
        }

        return implode("\n", [
            ...$lines,
            '',
            '## Optional',
            '',
            '- [Full product details]('.url('/llms-full.txt').'): Every product with complete English and Arabic descriptions and image links.',
            '- [Sitemap]('.url('/sitemap.xml').'): All public page URLs in Arabic and English.',
            '',
        ]);
    }

    public function full(): string
    {
        $lines = $this->header();
        $lines[] = '## Products';
        $lines[] = '';

        foreach ($this->products() as $p) {
            $lines[] = '### '.$this->name($p);
            $lines[] = '';
            if ($p->name_en && $p->name !== $p->name_en) {
                $lines[] = '- Arabic name: '.$p->name;
            }
            $lines[] = '- Price: '.$this->price($p);
            $lines[] = '- Category: '.lozan_t('category.'.$p->category, [], 'en');
            $lines[] = '- Availability: '.$this->availability($p);
            if ($colors = $this->colors($p)) {
                $lines[] = '- Colors: '.$colors;
            }
            if ($sizes = $this->sizes($p)) {
                $lines[] = '- Sizes: '.$sizes;
            }
            if ($p->material) {
                $lines[] = '- Material: '.$p->material;
            }
            $lines[] = '- Page (English): '.product_url($p, 'en');
            $lines[] = '- Page (Arabic): '.product_url($p, 'ar');
            foreach ($p->galleryUrls() as $i => $image) {
                $lines[] = '- Image '.($i + 1).': '.$image;
            }
            $english = trim((string) $p->description_en);
            $arabic = trim((string) $p->description);
            if ($english !== '') {
                $lines[] = '';
                $lines[] = $english;
            }
            if ($arabic !== '') {
                $lines[] = '';
                $lines[] = 'الوصف: '.$arabic;
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /** @return Collection<int, Product> */
    private function products(): Collection
    {
        return Product::query()
            ->indexable()
            ->with(['images', 'colors', 'sizes'])
            ->get()
            ->sortBy(fn (Product $p) => [$p->stockSortRank(), -$p->created_at?->timestamp])
            ->values();
    }

    private function header(): array
    {
        return [
            '# '.lozan_t('meta.siteName', [], 'en').' ('.lozan_t('meta.siteNameAlt', [], 'en').')',
            '',
            '> '.lozan_t('meta.jsonldDescription', [], 'en'),
            '',
            'Lozan is an online women\'s dress boutique based in Farwaniya, Kuwait, selling gowns, evening dresses and party wear.',
            '',
            '- Website: '.url('/').' (Arabic, default) and '.locale_url('', 'en').' (English)',
            '- Location: Farwaniya, Kuwait; delivery across Kuwait',
            '- Currency: Kuwaiti dinar (KWD), prices shown with 3 decimals',
            '- Ordering: add pieces to the bag and check out with a name and phone number. There is no online payment; the Lozan team contacts the customer to confirm the order, and payment is cash on delivery.',
            '- Sizes are European (EU). Some pieces marked "available to order" arrive from the supplier in about 10 days.',
            '',
        ];
    }

    private function name(Product $p): string
    {
        return $p->name_en ?: $p->name;
    }

    private function summary(Product $p): string
    {
        $parts = array_filter([
            $this->price($p),
            lozan_t('category.'.$p->category, [], 'en'),
            $this->availability($p),
            ($colors = $this->colors($p)) ? 'Colors: '.$colors : null,
            ($sizes = $this->sizes($p)) ? 'Sizes: '.$sizes : null,
        ]);
        $description = trim(preg_replace('/\s+/u', ' ', $p->localizedDescription('en')));
        if ($description !== '') {
            $parts[] = mb_strimwidth($description, 0, 160, '…');
        }

        return implode(' · ', $parts);
    }

    private function price(Product $p): string
    {
        $kwd = fn ($v) => number_format((float) $v, 3, '.', ',').' KWD';

        return is_on_sale($p)
            ? $kwd($p->offer_price).' (on sale, regular '.$kwd($p->price).')'
            : $kwd($p->price);
    }

    private function availability(Product $p): string
    {
        return match ($p->stock_status) {
            'in_stock' => 'In stock',
            'backorder' => 'Available to order (about 10 days)',
            default => 'Not available',
        };
    }

    private function colors(Product $p): string
    {
        return collect($p->colorArrays())
            ->map(fn ($c) => trim(($c['en'] ?: $c['ar']).($c['en'] && $c['ar'] ? ' ('.$c['ar'].')' : '')))
            ->filter()
            ->implode(', ');
    }

    private function sizes(Product $p): string
    {
        return collect($p->sizeValues())->map(fn ($s) => format_eu_size($s))->filter()->implode(', ');
    }
}
