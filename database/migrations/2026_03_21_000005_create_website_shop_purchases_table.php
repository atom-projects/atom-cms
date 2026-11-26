<?php

use App\Models\Shop\WebsiteShopPackage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_shop_purchases', function (Blueprint $table) {
            $table->id();

            $table->playerId('user_id');
            $table->foreignPlayer('user_id')->cascadeOnDelete();

            $table->foreignIdFor(WebsiteShopPackage::class)->constrained()->cascadeOnDelete();

            $table->playerId('gifted_to')->nullable();
            $table->foreignPlayer('gifted_to')->nullOnDelete();

            $table->timestamps();

            $table->index(['user_id', 'website_shop_package_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_shop_purchases');
    }
};
