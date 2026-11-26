<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The legacy shop articles were replaced by shop packages, and guestbook posts
 * live in user_home_messages. No code reads or writes these tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('website_shop_article_features');
        Schema::dropIfExists('website_shop_articles');
        Schema::dropIfExists('website_user_guestbooks');
    }

    /**
     * Restores the empty tables. give_rank keeps no foreign key: its target is
     * a different rank table on every driver, and nothing ever read it.
     */
    public function down(): void
    {
        Schema::create('website_shop_articles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('website_shop_category_id')->nullable()->constrained('website_shop_categories')->nullOnDelete();
            $table->string('name')->unique();
            $table->string('info');
            $table->string('icon_url');
            $table->string('color');
            $table->unsignedInteger('costs');
            $table->integer('give_rank')->nullable();
            $table->unsignedInteger('credits')->nullable();
            $table->unsignedInteger('duckets')->nullable();
            $table->unsignedInteger('diamonds')->nullable();
            $table->string('badges')->nullable();
            $table->json('furniture')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_giftable')->default(true);
            $table->timestamps();
        });

        Schema::create('website_shop_article_features', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')->constrained('website_shop_articles')->cascadeOnDelete();
            $table->string('content');
            $table->timestamps();
        });

        Schema::create('website_user_guestbooks', function (Blueprint $table): void {
            $table->id();
            $table->playerId('profile_id');
            $table->playerId('user_id');
            $table->string('message');
            $table->timestamps();
            $table->foreignPlayer('profile_id')->cascadeOnDelete();
            $table->foreignPlayer('user_id')->cascadeOnDelete();
        });
    }
};
