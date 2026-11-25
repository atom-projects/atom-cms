<?php

namespace App\Support;

use App\Emulator\Contracts\RankRepository;
use App\Services\HousekeepingPermissionsService;
use RuntimeException;
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
     * The ranks a shop rank item may give: the emulator's ranks below the
     * lowest staff rank. None when that staff rank cannot be determined.
     *
     * @return array<int|string, string>
     */
    public static function grantableRanks(): array
    {
        $staffRank = self::lowestStaffRank();

        return $staffRank === null ? [] : app(RankRepository::class)->optionsBelow($staffRank);
    }

    /**
     * The lowest of the staff rank setting, the housekeeping access rank and
     * the emulator's own staff ranks. Null, and reported, when a setting is
     * broken, so a broken setting never makes a staff rank grantable.
     */
    private static function lowestStaffRank(): ?int
    {
        $ceiling = self::ceiling();
        $staffRank = filter_var(setting('min_staff_rank', self::STAFF_RANK), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => $ceiling]]);

        if ($staffRank === false) {
            report(new RuntimeException("The min_staff_rank setting must be a whole number from 1 to {$ceiling}."));

            return null;
        }

        $housekeepingRank = app(HousekeepingPermissionsService::class)->minRank('can_access_housekeeping');

        if ($housekeepingRank !== null && $housekeepingRank < 1) {
            report(new RuntimeException('The can_access_housekeeping permission must have a rank of at least 1.'));

            return null;
        }

        $emulatorRank = app(RankRepository::class)->lowestStaffRank();

        return min($staffRank, $housekeepingRank ?? $staffRank, $emulatorRank ?? $staffRank);
    }

    public static function scale(int $rank): int
    {
        return min($rank, self::ceiling());
    }

    public static function ceiling(): int
    {
        // Never let seeding or migrating fail because the emulator schema is
        // not up yet.
        return self::top() ?? self::STAFF_RANK;
    }

    /**
     * The top rank, or null when the emulator schema cannot be read or has no
     * ranks. Use it for a permission that must not go to a guessed rank.
     */
    public static function top(): ?int
    {
        try {
            $highest = app(RankRepository::class)->highestRank();
        } catch (Throwable) {
            return null;
        }

        return $highest > 0 ? max(self::STAFF_RANK, $highest) : null;
    }
}
