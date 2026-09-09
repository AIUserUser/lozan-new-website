<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'legacy_id',
    'slug',
    'name',
    'name_en',
    'description',
    'price',
    'offer_price',
    'category',
    'published',
    'stock_status',
    'cover_index',
])]
class Product extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:3',
            'offer_price' => 'decimal:3',
            'published' => 'boolean',
            'cover_index' => 'integer',
        ];
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function colors(): HasMany
    {
        return $this->hasMany(ProductColor::class)->orderBy('sort_order');
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(ProductSize::class)->orderBy('sort_order');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published', true);
    }

    public function isInStock(): bool
    {
        return $this->stock_status === 'in_stock';
    }

    public function canBackorder(): bool
    {
        return $this->stock_status === 'backorder';
    }

    public function isUnavailable(): bool
    {
        return $this->stock_status === 'unavailable';
    }

    public function coverImage(): ?ProductImage
    {
        $images = $this->relationLoaded('images') ? $this->images : $this->images()->get();
        if ($images->isEmpty()) {
            return null;
        }
        $index = min(max(0, (int) $this->cover_index), $images->count() - 1);

        return $images[$index] ?? $images->first();
    }

    public function coverUrl(): ?string
    {
        $image = $this->coverImage();

        return $image ? $image->url() : null;
    }

    public function galleryUrls(): array
    {
        return $this->images->map(fn (ProductImage $image) => $image->url())->all();
    }

    public function colorArrays(): array
    {
        return $this->colors->map(fn (ProductColor $c) => [
            'ar' => $c->name_ar,
            'en' => $c->name_en,
            'hex' => $c->hex,
        ])->all();
    }

    public function sizeValues(): array
    {
        return $this->sizes->pluck('size')->all();
    }

    public function stockSortRank(): int
    {
        return match ($this->stock_status) {
            'in_stock' => 0,
            'unavailable' => 1,
            default => 2,
        };
    }

    public static function uniqueSlug(string $nameEn, string $nameAr, ?int $ignoreId = null): string
    {
        $base = Str::slug($nameEn ?: $nameAr);
        if ($base === '') {
            $base = 'product';
        }
        $slug = $base;
        $i = 2;
        while (static::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
