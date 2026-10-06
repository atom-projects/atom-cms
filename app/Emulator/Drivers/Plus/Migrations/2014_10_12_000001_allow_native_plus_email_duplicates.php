<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['website_users_username_unique', 'website_users_mail_unique'] as $index) {
            if (DB::selectOne("SHOW INDEX FROM website_users WHERE Key_name = '{$index}'") !== null) {
                DB::statement("ALTER TABLE website_users DROP INDEX {$index}");
            }
        }
    }

    public function down(): void {}
};
