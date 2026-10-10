<?php

use App\Emulator\Support\AtomPlayerColumns;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Atom's User model now lives on PlusEMU's users row, so the per-player data
 * PlusEMU has no column for moves there (as Arcturus keeps it on its users
 * row) and the website_users copy is dropped. Everything else website_users
 * held was a copy of PlusEMU data or unused.
 *
 * PlusEMU names the columns it reads, so the extra columns do not affect it.
 * Rolling back rebuilds website_users from users and PlusEMU's own tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        AtomPlayerColumns::add('users');

        if (! Schema::hasTable('website_users')) {
            return;
        }

        AtomPlayerColumns::copy('website_users', 'users');

        Schema::drop('website_users');
    }

    public function down(): void
    {
        $this->createWebsiteUsers();

        $activeRoles = 'FROM user_roles INNER JOIN roles ON roles.id = user_roles.role_id '
            . 'WHERE user_roles.user_id = users.id AND (user_roles.expires_at IS NULL OR user_roles.expires_at > UTC_TIMESTAMP(6))';
        DB::statement(<<<SQL
            INSERT INTO website_users
                (id, username, password, two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at, mail,
                account_created, last_login, last_online, motto, look, gender, rank, hidden_staff, native_role_id, credits,
                pixels, points, online, ip_register, ip_current, home_room, referral_code, website_balance, team_id, website_remember_token)
            SELECT users.id, users.username, users.password, users.two_factor_secret, users.two_factor_recovery_codes,
                users.two_factor_confirmed_at, users.mail, COALESCE(UNIX_TIMESTAMP(users.account_created), 0), users.last_login,
                COALESCE(UNIX_TIMESTAMP(users.last_online), 0), COALESCE(users.motto, ''), COALESCE(users.look, ''), COALESCE(users.gender, 'M'),
                COALESCE((SELECT MAX(roles.security_level) {$activeRoles}), 1), users.hidden_staff,
                (SELECT roles.id {$activeRoles} ORDER BY roles.security_level DESC, roles.weight DESC, roles.id LIMIT 1),
                COALESCE(users.credits, 0),
                COALESCE((SELECT amount FROM user_currencies WHERE user_currencies.user_id = users.id AND user_currencies.type = 0), 0),
                COALESCE((SELECT amount FROM user_currencies WHERE user_currencies.user_id = users.id AND user_currencies.type = 103), 0),
                COALESCE(users.online, 0), COALESCE(users.ip_reg, ''), COALESCE(users.ip_last, ''),
                COALESCE((SELECT home_room FROM users_settings WHERE users_settings.user_id = users.id), 0),
                users.referral_code, users.website_balance, users.team_id, users.website_remember_token
            FROM users
            SQL);

        AtomPlayerColumns::drop('users');
    }

    /** website_users exactly as it stood before this migration. */
    private function createWebsiteUsers(): void
    {
        Schema::create('website_users', function (Blueprint $table): void {
            $table->integer('id', autoIncrement: true);
            $table->string('username', 125);
            $table->string('real_name')->default('');
            $table->string('password')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->string('mail')->nullable();
            $table->string('mail_verified', 1)->default('0');
            $table->unsignedInteger('account_created')->default(0);
            $table->unsignedInteger('account_day_of_birth')->default(0);
            $table->unsignedInteger('last_login')->default(0);
            $table->unsignedInteger('last_online')->default(0);
            $table->string('motto', 50)->default('');
            $table->string('look', 255)->default('');
            $table->string('gender', 1)->default('M');
            $table->unsignedInteger('rank')->default(1);
            $table->boolean('hidden_staff')->default(false);
            $table->unsignedInteger('native_role_id')->nullable();
            $table->integer('credits')->default(0);
            $table->integer('pixels')->default(0);
            $table->integer('points')->default(0);
            $table->boolean('online')->default(false);
            $table->string('auth_ticket')->default('');
            $table->string('ip_register', 45)->default('');
            $table->string('ip_current', 45)->default('');
            $table->string('machine_id', 125)->default('');
            $table->unsignedInteger('home_room')->default(0);
            $table->string('referral_code')->nullable();
            $table->unsignedBigInteger('website_balance')->default(0);
            $table->string('secret_key', 40)->nullable();
            $table->string('pincode', 11)->nullable();
            $table->unsignedInteger('extra_rank')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('website_remember_token', 100)->nullable();

            $table->unique('referral_code');
            $table->index('team_id', 'website_users_team_id_foreign');
            $table->index('ip_register', 'users_ip_register_registration_index');
            $table->index('ip_current', 'users_ip_current_registration_index');
            $table->foreign('team_id')->references('id')->on('website_teams')->nullOnDelete();
        });
    }
};
