<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('website_users')) {
            return;
        }
        if (! Schema::hasTable('users')) {
            throw new RuntimeException('PlusEMU users table was not found.');
        }

        Schema::create('website_users', function (Blueprint $table): void {
            $table->integer('id', autoIncrement: true);
            $table->string('username', 125);
            $table->string('real_name')->default('');
            $table->string('password')->nullable();
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
            $table->string('secret_key', 40)->nullable();
            $table->string('pincode', 11)->nullable();
            $table->unsignedInteger('extra_rank')->nullable();
        });

        DB::table('users')->leftJoin('users_settings', 'users_settings.user_id', '=', 'users.id')->orderBy('users.id')
            ->select(['users.*', 'users_settings.home_room'])->chunk(250, function ($rows): void {
                $activeRoles = DB::table('user_roles')->join('roles', 'roles.id', '=', 'user_roles.role_id')
                    ->whereIn('user_id', $rows->pluck('id'))
                    ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now('UTC')->format('Y-m-d H:i:s.u')))
                    ->orderByDesc('roles.security_level')->orderByDesc('roles.weight')->orderBy('roles.id')
                    ->get(['user_id', 'role_id', 'roles.security_level'])->unique('user_id')->keyBy('user_id');
                DB::table('website_users')->insert($rows->map(fn (object $row): array => [
                    'id' => $row->id, 'username' => $row->username, 'password' => $row->password,
                    'mail' => $row->mail, 'account_created' => $this->unix($row->account_created),
                    'last_online' => $this->unix($row->last_online), 'motto' => $row->motto ?? '',
                    'look' => $row->look ?? '', 'gender' => $row->gender ?? 'M',
                    'rank' => (int) ($activeRoles[$row->id]->security_level ?? 1),
                    'native_role_id' => $activeRoles[$row->id]->role_id ?? null, 'credits' => (int) $row->credits,
                    'pixels' => (int) $row->activity_points, 'points' => (int) $row->gotw_points,
                    'online' => (bool) $row->online, 'ip_register' => $row->ip_reg ?? '',
                    'ip_current' => $row->ip_last ?? '', 'home_room' => (int) ($row->home_room ?? 0),
                ])->all());
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_users');
    }

    private function unix(mixed $value): int
    {
        return $value === null ? 0 : Carbon::parse($value)->unix();
    }
};
