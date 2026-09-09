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
                $keys = collect($p->colorArrays())->map(fn ($c) => color_key($c))->all();
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

    public function colorOptions(Collection $products): array
    {
        $map = [];
        foreach ($products as $p) {
            foreach ($p->colorArrays() as $c) {
                $key = color_key($c);
                if (trim(str_replace('|', '', $key)) === '') {
                    continue;
                }
                if (! isset($map[$key])) {
                    $map[$key] = ['key' => $key, 'color' => $c, 'label' => color_label($c)];
                }
            }
        }
        $opts = array_values($map);
        usort($opts, fn ($a, $b) => strcmp($a['label'], $b['label']));

        return $opts;
    }

    public function sizeOptions(Collection $products): array
    {
        $set = [];
        foreach ($products as $p) {
            foreach ($p->sizeValues() as $s) {
                $n = trim(preg_replace('/^eu\s*/i', '', (string) $s) ?? (string) $s);
                if ($n !== '') {
                    $set[$n] = $n;
                }
            }
        }
        $vals = array_values($set);
        usort($vals, function ($a, $b) {
            if (is_numeric($a) && is_numeric($b)) {
                return (float) $a <=> (float) $b;
            }

            return strcmp($a, $b);
        });

        return $vals;
    }
}
