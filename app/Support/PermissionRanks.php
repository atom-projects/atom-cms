<?php

namespace App\Support;

use App\Emulator\Contracts\RankRepository;
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
