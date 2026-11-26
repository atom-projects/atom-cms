<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(cmsUserTable(), function (Blueprint $table) {
            $table->unsignedInteger('website_balance')->default(0)->after('referral_code');
        });
    }

    public function down(): void
    {
        $users = cmsUserTable();
        Schema::table($users, function (Blueprint $table) {
            $table->dropColumn('website_balance');
        });
    }
};
