<?php

namespace App\Support;

use App\Emulator\Contracts\RankRepository;
use App\Services\HousekeepingPermissionsService;
use Throwable;

/**
 * Keeps permission ranks inside the range the emulator actually has.
 *
 * Atom's defaults were written against Arcturus, whose permissions table runs
 * past the owner tier. Ada's base roles stop at Admin, so a permission left at
 * a higher rank than any role is unreachable by everyone - the hotel owner
 * simply never sees the feature, with no error to explain why.
 */
final class PermissionRanks
{
    /**
     * Owner-level permissions never drop below the staff tier, so a hotel that
     * has not defined ranks above it does not hand them to ordinary players.
     */
    public const STAFF_RANK = 6;

    /**
     * The lowest rank that counts as staff: the staff rank setting, or the
     * housekeeping access rank when that is lower.
     */
    public static function lowestStaffRank(): int
    {
        $staffRank = (int) setting('min_staff_rank', self::STAFF_RANK);
        $housekeepingRank = app(HousekeepingPermissionsService::class)->minRank('can_access_housekeeping');

        return $housekeepingRank === null ? $staffRank : min($staffRank, $housekeepingRank);
    }

    public static function scale(int $rank): int
    {
        return min($rank, self::ceiling());
    }

    public static function ceiling(): int
    {
        try {
            return max(self::STAFF_RANK, app(RankRepository::class)->highestRank());
        } catch (Throwable) {
            // Never let seeding or migrating fail because the emulator schema
            // is not up yet.
            return self::STAFF_RANK;
        }
    }
}
