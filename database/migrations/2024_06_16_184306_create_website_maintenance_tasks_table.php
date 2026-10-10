<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_maintenance_tasks', function (Blueprint $table) {
            $table->id();

            $table->playerId('user_id')->nullable();
            $table->string('task');
            $table->boolean('completed')->default(false);

            $table->timestamps();

            $table->foreignPlayer('user_id')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_maintenance_tasks');
    }
};
