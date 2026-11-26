<?php

use App\Models\WebsiteHousekeepingPermission;
use App\Support\PermissionRanks;
use App\Support\WebsiteSettingRules;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\ConsoleOutput;

return new class extends Migration
{
    /**
     * Gates the website settings that decide where login tickets, file writes
     * and RCON commands go, and who must use two-factor. Only the top rank
     * gets it. Without a known top rank the row is not created, and a missing
     * row denies everyone.
     */
    public function up(): void
    {
        $topRank = PermissionRanks::top();

        if ($topRank === null) {
            Log::warning(WebsiteSettingRules::SENSITIVE_PERMISSION_SKIPPED);

            if (app()->runningInConsole()) {
                (new ConsoleOutput)->getErrorOutput()->writeln(WebsiteSettingRules::SENSITIVE_PERMISSION_SKIPPED);
            }

            return;
        }

        WebsiteHousekeepingPermission::query()->firstOrCreate(
            ['permission' => WebsiteSettingRules::SENSITIVE_PERMISSION],
            [
                'min_rank' => $topRank,
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
