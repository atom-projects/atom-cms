<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_staff_applications', function (Blueprint $table) {
            $table->id();
            $table->playerId('user_id');
            $table->integer('rank_id');
            $table->text('content');
            $table->timestamps();

            $table->foreignPlayer('user_id')->cascadeOnDelete();
            if (config('emulator.driver') !== 'plus') {
                $table->foreign('rank_id')->references('id')->on('permissions')->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_staff_applications');
    }
};
