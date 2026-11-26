<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_article_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('article_id');
            $table->playerId('user_id');
            $table->string('comment');
            $table->timestamps();

            $table->foreign('article_id')->references('id')->on('website_articles')->cascadeOnDelete();
            $table->foreignPlayer('user_id')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_article_comments');
    }
};
