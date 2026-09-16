<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_subscribers', function (Blueprint $table) {
            // Group chat ids are negative, so the id must be signed.
            $table->bigInteger('chat_id')->change();
            $table->string('label')->nullable()->after('chat_id');
        });
    }

    public function down(): void
    {
        Schema::table('telegram_subscribers', function (Blueprint $table) {
            $table->dropColumn('label');
            $table->unsignedBigInteger('chat_id')->change();
        });
    }
};
