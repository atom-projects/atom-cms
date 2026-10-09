<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Arcturus-era columns the PlusEMU projection created but nothing reads or
 * writes. The User model keeps them guarded and hidden for the drivers whose
 * users table still has them.
 */
return new class extends Migration
{
    private const COLUMNS = ['real_name', 'mail_verified', 'account_day_of_birth', 'machine_id', 'secret_key', 'pincode', 'extra_rank'];

    public function up(): void
    {
        $columns = array_values(array_filter(self::COLUMNS, fn (string $column): bool => Schema::hasColumn('website_users', $column)));

        if ($columns !== []) {
            Schema::table('website_users', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }

    public function down(): void
    {
        Schema::table('website_users', function (Blueprint $table): void {
            $table->string('real_name')->default('')->after('username');
            $table->string('mail_verified', 1)->default('0')->after('mail');
            $table->unsignedInteger('account_day_of_birth')->default(0)->after('account_created');
            $table->string('machine_id', 125)->default('')->after('ip_current');
            $table->string('secret_key', 40)->nullable()->after('website_balance');
            $table->string('pincode', 11)->nullable()->after('secret_key');
            $table->unsignedInteger('extra_rank')->nullable()->after('pincode');
        });
    }
};
