<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * website_users is Atom's half of a PlusEMU player, so its id now references
 * users.id and disappears with the player. Rows left behind by players PlusEMU
 * already deleted are removed first; nothing references website_users.
 *
 * username and mail stay non-unique on purpose: PlusEMU enforces them on users,
 * and the projection can briefly hold a renamed player's old name while the
 * player who took it is projected. A unique index would turn that window into
 * failed or misdirected upserts.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('website_users')
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('users')->whereColumn('users.id', 'website_users.id'))
            ->delete();

        Schema::table('website_users', function (Blueprint $table): void {
            $table->foreign('id', 'website_users_id_foreign')->references('id')->on('users')->cascadeOnDelete();
            $table->index('username', 'website_users_username_index');
            $table->index('mail', 'website_users_mail_index');
            $table->index('rank', 'website_users_rank_index');
        });
    }

    public function down(): void
    {
        Schema::table('website_users', function (Blueprint $table): void {
            $table->dropForeign('website_users_id_foreign');
            $table->dropIndex('website_users_username_index');
            $table->dropIndex('website_users_mail_index');
            $table->dropIndex('website_users_rank_index');
        });
    }
};
