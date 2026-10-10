<?php

use App\Emulator\Support\AtomPlayerColumns;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Atom's User model now lives on Ada's players row (see AdaDriver's player
 * schema), so the users compatibility table Atom used to keep in step with Ada
 * goes, and so does the empty camera_web stand-in: Ada stores no photos and
 * the photo pages are gated on the CameraPhotos feature instead.
 *
 * Atom's own per-player columns move onto players, and every Atom table that
 * referenced users.id (a signed INT) references players.id (a signed BIGINT)
 * with the same delete rule. Everything else the compatibility row held was a
 * copy of Ada's data. Ada keeps last_login in player_website_data.
 *
 * Rolling back rebuilds the compatibility table from Ada's tables and points
 * Atom's keys back at it. Ada's own keys, which EF names fk_*, are not touched.
 */
return new class extends Migration
{
    /** Ada keeps this one itself. */
    private const ADA_OWNED = ['last_login'];

    public function up(): void
    {
        Schema::dropIfExists('camera_web');
        AtomPlayerColumns::add('players', self::ADA_OWNED);

        if (! Schema::hasTable('users')) {
            return;
        }

        AtomPlayerColumns::copy('users', 'players', self::ADA_OWNED);

        $this->repoint('users', 'players', 'bigInteger');

        Schema::drop('users');
    }

    public function down(): void
    {
        $this->createCompatibilityTable();

        $this->repoint('players', 'users', 'integer', atomOnly: true);

        AtomPlayerColumns::drop('players', self::ADA_OWNED);

        Schema::create('camera_web', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->integer('room_id')->default(0);
            $table->unsignedInteger('timestamp');
            $table->string('url', 128)->default('');
        });
    }

    /**
     * Point every foreign key on $from.id at $to.id instead, retyping the
     * referencing column and keeping its nullability and delete rule.
     */
    private function repoint(string $from, string $to, string $type, bool $atomOnly = false): void
    {
        $keys = DB::table('information_schema.KEY_COLUMN_USAGE as k')
            ->join('information_schema.REFERENTIAL_CONSTRAINTS as r', fn ($join) => $join
                ->on('r.CONSTRAINT_SCHEMA', '=', 'k.CONSTRAINT_SCHEMA')
                ->on('r.TABLE_NAME', '=', 'k.TABLE_NAME')
                ->on('r.CONSTRAINT_NAME', '=', 'k.CONSTRAINT_NAME'))
            ->join('information_schema.COLUMNS as c', fn ($join) => $join
                ->on('c.TABLE_SCHEMA', '=', 'k.TABLE_SCHEMA')
                ->on('c.TABLE_NAME', '=', 'k.TABLE_NAME')
                ->on('c.COLUMN_NAME', '=', 'k.COLUMN_NAME'))
            ->whereRaw('k.TABLE_SCHEMA = DATABASE()')
            ->where('k.REFERENCED_TABLE_NAME', $from)
            ->where('k.REFERENCED_COLUMN_NAME', 'id')
            ->when($atomOnly, fn ($query) => $query->where('k.CONSTRAINT_NAME', 'not like', 'fk\_%'))
            ->get(['k.TABLE_NAME as table', 'k.COLUMN_NAME as column', 'k.CONSTRAINT_NAME as name', 'r.DELETE_RULE as rule', 'c.IS_NULLABLE as nullable']);

        foreach ($keys as $key) {
            Schema::table($key->table, fn (Blueprint $table) => $table->dropForeign($key->name));

            Schema::table($key->table, function (Blueprint $table) use ($key, $to, $type): void {
                $table->{$type}($key->column)->nullable($key->nullable === 'YES')->change();
                $foreign = $table->foreign($key->column, $key->name)->references('id')->on($to);
                match ($key->rule) {
                    'CASCADE' => $foreign->cascadeOnDelete(),
                    'SET NULL' => $foreign->nullOnDelete(),
                    default => $foreign->restrictOnDelete(),
                };
            });
        }
    }

    /** The compatibility table as Atom kept it, filled from Ada's tables. */
    private function createCompatibilityTable(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->integer('id', autoIncrement: true);
            $table->string('username', 50)->unique();
            $table->string('real_name', 50)->default('');
            $table->string('password', 60);
            $table->string('mail', 255)->nullable();
            $table->string('mail_verified', 1)->default('0');
            $table->unsignedInteger('account_created')->default(0);
            $table->unsignedInteger('account_day_of_birth')->default(0);
            $table->unsignedInteger('last_login')->default(0);
            $table->unsignedInteger('last_online')->default(0);
            $table->string('motto', 127)->default('');
            $table->string('look', 256)->default('');
            $table->string('gender', 1)->default('M');
            $table->unsignedInteger('rank')->default(1);
            $table->integer('credits')->default(0);
            $table->integer('pixels')->default(0);
            $table->integer('points')->default(0);
            $table->boolean('online')->default(false);
            $table->string('auth_ticket', 256)->default('')->index();
            $table->string('ip_register', 45)->default('');
            $table->string('ip_current', 45)->default('');
            $table->string('machine_id', 64)->default('');
            $table->unsignedInteger('home_room')->default(0);
            $table->string('secret_key', 40)->nullable();
            $table->string('pincode', 11)->nullable();
            $table->unsignedInteger('extra_rank')->nullable();
        });

        AtomPlayerColumns::add('users');

        $atom = array_values(array_diff(AtomPlayerColumns::names(), self::ADA_OWNED));

        DB::table('players')
            ->leftJoin('player_avatar_data', 'player_avatar_data.player_id', '=', 'players.id')
            ->leftJoin('player_data', 'player_data.player_id', '=', 'players.id')
            ->leftJoin('player_website_data', 'player_website_data.player_id', '=', 'players.id')
            ->orderBy('players.id')
            ->select([
                'players.*', 'player_avatar_data.figure_code', 'player_avatar_data.motto', 'player_avatar_data.gender',
                'player_data.home_room_id', 'player_data.credit_balance', 'player_data.pixel_balance', 'player_data.gotw_points',
                'player_data.is_online', 'player_data.last_online', 'player_website_data.initial_ip', 'player_website_data.last_ip',
                'player_website_data.last_login',
            ])
            ->chunk(250, function ($players) use ($atom): void {
                $roleIds = DB::table('player_role')
                    ->whereIn('player_id', $players->pluck('id'))
                    ->selectRaw('player_id, MAX(role_id) as role_id')
                    ->groupBy('player_id')
                    ->pluck('role_id', 'player_id');

                DB::table('users')->insert($players->map(fn (object $player): array => [
                    'id' => $player->id,
                    'username' => $player->username,
                    'password' => $player->password,
                    'mail' => $player->email,
                    'account_created' => $this->unix($player->created_at),
                    'last_login' => $this->unix($player->last_login),
                    'last_online' => $this->unix($player->last_online),
                    'motto' => $player->motto ?? '',
                    'look' => $player->figure_code ?? '',
                    'gender' => $player->gender ?? 'M',
                    'rank' => (int) ($roleIds[$player->id] ?? 1),
                    'credits' => (int) ($player->credit_balance ?? 0),
                    'pixels' => (int) ($player->pixel_balance ?? 0),
                    'points' => (int) ($player->gotw_points ?? 0),
                    'online' => (bool) ($player->is_online ?? false),
                    'ip_register' => $player->initial_ip ?? '',
                    'ip_current' => $player->last_ip ?? '',
                    'home_room' => (int) ($player->home_room_id ?? 0),
                    ...array_combine($atom, array_map(fn (string $column): mixed => $player->{$column} ?? null, $atom)),
                ])->all());
            });
    }

    private function unix(mixed $value): int
    {
        return $value === null ? 0 : Carbon::parse($value)->unix();
    }
};
