<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_help_center_ticket_replies', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('ticket_id');
            $table->playerId('user_id');

            $table->text('content');

            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('website_help_center_tickets')->cascadeOnDelete();
            $table->foreignPlayer('user_id')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_help_center_ticket_replies');
    }
};
