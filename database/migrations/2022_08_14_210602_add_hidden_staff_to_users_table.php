<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $users = cmsUserTable();
        if (! Schema::hasColumn($users, 'hidden_staff')) {
            Schema::table($users, function (Blueprint $table) {
                $table->boolean('hidden_staff')->default(false);
            });
        }
    }

    public function down(): void
    {
        $users = cmsUserTable();
        Schema::table($users, function (Blueprint $table) {
            $table->dropColumn('hidden_staff');
        });
    }
};
