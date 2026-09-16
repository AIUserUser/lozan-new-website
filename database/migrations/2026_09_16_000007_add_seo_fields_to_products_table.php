<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('description_en')->nullable()->after('description');
            // Optional overrides; empty means "generate automatically".
            $table->string('seo_title_ar')->nullable()->after('cover_index');
            $table->string('seo_title_en')->nullable()->after('seo_title_ar');
            $table->string('seo_description_ar', 320)->nullable()->after('seo_title_en');
            $table->string('seo_description_en', 320)->nullable()->after('seo_description_ar');
            $table->boolean('seo_noindex')->default(false)->after('seo_description_en');
            // Google merchant listing / product rich result identifiers.
            $table->string('brand', 100)->nullable()->after('seo_noindex');
            $table->string('sku', 64)->nullable()->after('brand');
            $table->string('gtin', 14)->nullable()->after('sku');
            $table->string('mpn', 70)->nullable()->after('gtin');
            $table->string('material', 100)->nullable()->after('mpn');
        });

        // Old product URLs keep working (301) after a slug change.
        Schema::create('product_slug_redirects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_slug_redirects');
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'description_en', 'seo_title_ar', 'seo_title_en', 'seo_description_ar', 'seo_description_en',
                'seo_noindex', 'brand', 'sku', 'gtin', 'mpn', 'material',
            ]);
        });
    }
};
