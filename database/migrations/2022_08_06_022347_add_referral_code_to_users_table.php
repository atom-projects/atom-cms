<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $users = cmsUserTable();
        if (! Schema::hasColumn($users, 'referral_code')) {
            Schema::table($users, function (Blueprint $table) {
                $table->string('referral_code')->nullable()->unique();
            });
        }

        DB::table($users)
            ->whereNull('referral_code')
            ->orderBy('id')
            ->eachById(function (object $user) use ($users) {
                DB::table($users)
                    ->where('id', $user->id)
                    ->update(['referral_code' => sprintf('%s%s', $user->id, Str::random(8))]);
            });
    }

    public function down(): void
    {
        $users = cmsUserTable();
        if (Schema::hasColumn($users, 'referral_code')) {
            Schema::table($users, function (Blueprint $table) {
                $table->dropColumn('referral_code');
            });
        }
    }
};
