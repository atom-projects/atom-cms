<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_user_guestbooks', function (Blueprint $table) {
            $table->id();

            $table->playerId('profile_id');
            $table->playerId('user_id');
            $table->string('message');

            $table->timestamps();

            $table->foreignPlayer('profile_id')->cascadeOnDelete();
            $table->foreignPlayer('user_id')->cascadeOnDelete();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_user_guestbooks');
    }
};
