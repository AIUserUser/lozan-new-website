<?php

namespace App\Services;

use App\Models\Product;

class SitemapService
{
    public function urls(): array
    {
        $products = Product::query()->published()->get();
        $latest = optional($products->max('updated_at'))->toAtomString();
        $urls = [];
        foreach (['ar', 'en'] as $locale) {
            $urls[] = ['loc' => locale_url('', $locale), 'changefreq' => 'daily', 'priority' => '1.0', 'lastmod' => $latest];
            $urls[] = ['loc' => locale_url('shop', $locale), 'changefreq' => 'daily', 'priority' => '0.9', 'lastmod' => $latest];
            foreach ($products as $p) {
                $urls[] = [
                    'loc' => product_url($p, $locale),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                    'lastmod' => optional($p->updated_at)->toAtomString(),
                ];
            }
        }

        return $urls;
    }

    public function render(?array $urls = null): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .view('store.sitemap', ['urls' => $urls ?? $this->urls()])->render();
    }

    public function write(string $path): int
    {
        $urls = $this->urls();
        $tmp = $path.'.tmp';
        file_put_contents($tmp, $this->render($urls));
        chmod($tmp, 0644);
        rename($tmp, $path);

        return count($urls);
    }
}
