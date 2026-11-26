<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_home_ratings', function (Blueprint $table) {
            $table->id();
            $table->playerId('user_id');
            $table->foreignPlayer('user_id')->cascadeOnDelete();
            $table->playerId('rated_user_id');
            $table->foreignPlayer('rated_user_id')->cascadeOnDelete();
            $table->integer('rating');

            $table->unique(['user_id', 'rated_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_home_ratings');
    }
};
