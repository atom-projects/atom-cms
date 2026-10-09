<?php

use App\Emulator\Emulator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Index name => the attribute registration counts accounts by. */
    private const INDEXES = [
        'users_ip_register_registration_index' => 'ip_register',
        'users_ip_current_registration_index' => 'ip_current',
    ];

    public function up(): void
    {
        Schema::create('website_registration_locks', function (Blueprint $table) {
            $table->string('lock_key', 64)->primary();
        });

        $users = cmsUserTable();
        foreach ($this->columns($users) as $name => $column) {
            if (! Schema::hasIndex($users, [$column])) {
                Schema::table($users, fn (Blueprint $table) => $table->index($column, $name));
            }
        }
    }

    public function down(): void
    {
        $users = cmsUserTable();
        foreach (array_keys(self::INDEXES) as $name) {
            if (Schema::hasIndex($users, $name)) {
                Schema::table($users, fn (Blueprint $table) => $table->dropIndex($name));
            }
        }

        Schema::dropIfExists('website_registration_locks');
    }

    /**
     * The indexed columns as the table names them. An emulator that keeps the
     * addresses in another table indexes them there itself.
     *
     * @return array<string, string>
     */
    private function columns(string $users): array
    {
        $renamed = $users === 'website_users' ? [] : Emulator::playerSchema()->columns;

        return array_filter(
            array_map(fn (string $attribute): string => $renamed[$attribute] ?? $attribute, self::INDEXES),
            fn (string $column): bool => Schema::hasColumn($users, $column),
        );
    }
};
