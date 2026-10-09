<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_home_messages', function (Blueprint $table) {
            $table->id();
            $table->playerId('user_id');
            $table->foreignPlayer('user_id')->cascadeOnDelete();
            $table->playerId('recipient_user_id');
            $table->foreignPlayer('recipient_user_id')->cascadeOnDelete();
            $table->text('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_home_messages');
    }
};
