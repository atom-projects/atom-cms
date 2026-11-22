<?php

use App\Models\WebsiteHousekeepingPermission;
use App\Support\PermissionRanks;
use App\Support\WebsiteSettingRules;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Gates the website settings that decide where login tickets, file writes
     * and RCON commands go, and who must use two-factor. Only the top rank
     * gets it.
     */
    public function up(): void
    {
        WebsiteHousekeepingPermission::query()->firstOrCreate(
            ['permission' => WebsiteSettingRules::SENSITIVE_PERMISSION],
            [
                'min_rank' => PermissionRanks::ceiling(),
                'description' => 'The minimum rank required before being able to change the client, file path, RCON and staff two-factor website settings',
            ],
        );
    }

    public function down(): void
    {
        WebsiteHousekeepingPermission::query()
            ->where('permission', WebsiteSettingRules::SENSITIVE_PERMISSION)
            ->delete();
    }
};
