<?php

namespace Database\Seeders;

use App\Models\Miscellaneous\WebsiteInstallation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestingSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::table((new User)->getTable())->delete();

        WebsiteInstallation::query()->firstOrCreate(['installation_key' => 'key'], ['completed' => true]);

        $this->call([
            WebsiteSettingsSeeder::class,
            WebsiteLanguageSeeder::class,
            WebsitePermissionSeeder::class,
        ]);
    }
}
