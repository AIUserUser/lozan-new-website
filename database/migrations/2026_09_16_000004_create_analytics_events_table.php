<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('visitor_id');
            $table->string('session_hash', 40);
            $table->string('type', 24);
            $table->string('page', 24)->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity')->nullable();
            $table->decimal('value', 12, 3)->nullable();
            $table->string('color', 80)->nullable();
            $table->string('size', 20)->nullable();
            $table->string('locale', 2);
            $table->string('device', 10);
            $table->string('source', 32);
            $table->string('referrer_host')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['type', 'created_at']);
            $table->index(['product_id', 'type', 'created_at']);
            $table->index(['visitor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
