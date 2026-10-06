<?php

namespace App\Emulator\Drivers\Plus;

use App\Emulator\Contracts\BanRepository;
use App\Emulator\Data\BanInfo;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class PlusBanRepository implements BanRepository
{
    public function activeIpBan(string $ip): ?BanInfo
    {
        return $this->find(DB::table('bans')->where('value', $ip)->where('bantype', 'ip'));
    }

    public function activeAccountBan(User $user): ?BanInfo
    {
        return $this->find(DB::table('bans')->where('value', $user->username)->where('bantype', 'user'));
    }

    private function find(Builder $query): ?BanInfo
    {
        $row = $query->where('expire', '>', now('UTC')->format('Y-m-d H:i:s.u'))->orderByDesc('expire')->first();

        return $row === null ? null : new BanInfo(
            (string) $row->bantype,
            (string) $row->reason,
            $row->expire === null ? null : Carbon::parse($row->expire)->unix(),
        );
    }
}
