<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claimed_referral_logs', function (Blueprint $table) {
            $table->id();
            $table->playerId('user_id')->index();
            $table->string('ip_address');
            $table->timestamps();

            $table->foreignPlayer('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claimed_referral_logs');
    }
};
