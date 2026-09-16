<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class CatalogService
{
    public function published(): Collection
    {
        return Product::query()
            ->published()
            ->with(['images', 'colors', 'sizes'])
            ->get()
            ->sort(function (Product $a, Product $b) {
                if ($a->stockSortRank() !== $b->stockSortRank()) {
                    return $a->stockSortRank() <=> $b->stockSortRank();
                }

                return $b->created_at?->timestamp <=> $a->created_at?->timestamp;
            })
            ->values();
    }

    public function filter(Collection $products, ?string $category, ?string $colorKey, ?string $size): Collection
    {
        $category = $category ?: 'all';
        $size = $size ? trim(preg_replace('/^eu\s*/i', '', $size) ?? $size) : null;

        return $products->filter(function (Product $p) use ($category, $colorKey, $size) {
            if ($category !== 'all' && $p->category !== $category) {
                return false;
            }
            if ($colorKey) {
                $keys = collect($p->colorArrays())->map(fn ($c) => color_filter_key($c))->all();
                if (! in_array($colorKey, $keys, true)) {
                    return false;
                }
            }
            if ($size) {
                $sizes = collect($p->sizeValues())->map(fn ($s) => trim(preg_replace('/^eu\s*/i', '', (string) $s) ?? (string) $s))->all();
                if (! in_array($size, $sizes, true)) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    /**
     * Color and size choices with product counts. Each facet is counted against
     * the other active filters, so a choice never leads to an empty page;
     * the selected value is always kept.
     *
     * @return array{colors: array<int, array{key: string, label: string, hex: ?string, count: int}>, sizes: array<int, array{value: string, count: int}>}
     */
    public function facets(Collection $products, ?string $category, ?string $colorKey, ?string $size): array
    {
        $size = $size ? trim(preg_replace('/^eu\s*/i', '', $size) ?? $size) : null;
        $colors = [];
        foreach ($this->filter($products, $category, null, $size) as $p) {
            $seen = [];
            foreach ($p->colorArrays() as $c) {
                $key = color_filter_key($c);
                if ($key === '' || isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $colors[$key] ??= ['key' => $key, 'label' => color_label($c), 'hex' => $c['hex'] ?: null, 'count' => 0];
                $colors[$key]['count']++;
            }
        }
        if ($colorKey && ! isset($colors[$colorKey])) {
            $match = $products->flatMap(fn (Product $p) => $p->colorArrays())->first(fn ($c) => color_filter_key($c) === $colorKey);
            if ($match) {
                $colors[$colorKey] = ['key' => $colorKey, 'label' => color_label($match), 'hex' => $match['hex'] ?: null, 'count' => 0];
            }
        }
        $colors = array_values($colors);
        usort($colors, fn ($a, $b) => [$b['count'], $a['label']] <=> [$a['count'], $b['label']]);

        $sizes = [];
        foreach ($this->filter($products, $category, $colorKey, null) as $p) {
            foreach (array_unique($p->sizeValues()) as $s) {
                $n = trim(preg_replace('/^eu\s*/i', '', (string) $s) ?? (string) $s);
                if ($n !== '') {
                    $sizes[$n] = ['value' => $n, 'count' => ($sizes[$n]['count'] ?? 0) + 1];
                }
            }
        }
        if ($size && ! isset($sizes[$size])) {
            $sizes[$size] = ['value' => $size, 'count' => 0];
        }
        $sizes = array_values($sizes);
        usort($sizes, fn ($a, $b) => is_numeric($a['value']) && is_numeric($b['value'])
            ? (float) $a['value'] <=> (float) $b['value']
            : strcmp($a['value'], $b['value']));

        return ['colors' => $colors, 'sizes' => $sizes];
    }
}
