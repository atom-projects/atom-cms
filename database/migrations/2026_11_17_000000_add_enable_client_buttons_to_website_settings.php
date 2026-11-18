<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('website_settings')->insertOrIgnore([
            'key' => 'enable_client_buttons',
            'value' => '0',
            'comment' => 'Shows the home, reload, fullscreen and online count buttons in the client (0 = off, 1 = on)',
        ]);
    }

    public function down(): void
    {
        DB::table('website_settings')->where('key', 'enable_client_buttons')->delete();
    }
};
