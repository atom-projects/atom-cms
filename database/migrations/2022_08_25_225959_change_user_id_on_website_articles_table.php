<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_articles', function (Blueprint $table) {
            $table->playerId('user_id')->nullable()->change();
            $table->foreignPlayer('user_id')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('website_articles', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->string('user_id')->nullable(false)->change();
        });
    }
};
