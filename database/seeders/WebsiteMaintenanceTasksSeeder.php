<?php

namespace Database\Seeders;

use App\Emulator\Contracts\RankRepository;
use App\Models\Miscellaneous\WebsiteMaintenanceTask;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WebsiteMaintenanceTasksSeeder extends Seeder
{
    public function run(): void
    {
        $highestRank = app(RankRepository::class)->highestRank();
        $user = User::where('rank', $highestRank)->first();

        if ($user === null) {
            $user = User::query()->forceCreate([
                'username' => 'Admin',
                'mail' => 'admin@example.com',
                'password' => Hash::make(Str::password()),
                'account_created' => time(),
                'last_login' => time(),
                'motto' => 'Atom',
                'look' => 'fa-201407-1324.hr-828-1035.ch-3001-1261-1408.sh-3068-92-1408.cp-9032-1308.lg-270-1281.hd-209-3',
                'credits' => 0,
                'ip_register' => '127.0.0.1',
                'ip_current' => '127.0.0.1',
                'home_room' => 0,
                'rank' => $highestRank,
            ]);
        }

        WebsiteMaintenanceTask::firstOrCreate(['task' => 'Working on the hotel'], [
            'user_id' => $user->id,
            'completed' => false,
        ]);
    }
}
