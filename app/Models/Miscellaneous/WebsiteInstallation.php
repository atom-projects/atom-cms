<?php

namespace App\Models\Miscellaneous;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $step
 * @property int $completed
 * @property string|null $installation_key
 * @property string|null $user_ip
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteInstallation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteInstallation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteInstallation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteInstallation whereCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteInstallation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteInstallation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteInstallation whereInstallationKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteInstallation whereStep($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteInstallation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebsiteInstallation whereUserIp($value)
 *
 * @mixin \Eloquent
 */
class WebsiteInstallation extends Model
{
    /** Settings are split evenly across this many wizard steps. */
    public const SETTING_STEPS = 4;

    /** Step zero is the key-entry screen, before the wizard proper. */
    public const FIRST_STEP = 1;

    /** The settings steps plus the completion screen. */
    public const LAST_STEP = self::SETTING_STEPS + 1;

    protected $table = 'website_installation';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * The step is the single value the wizard and its middleware both navigate
     * by, so it is bounded here rather than at each of them. A row that
     * already ran past the last step - an older build let a resubmitted form
     * do that - reads back as the last step and heals itself on the next save.
     * A missing step reads back as step zero, the key-entry screen.
     *
     * @return Attribute<int, int|null>
     */
    protected function step(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): int => $this->boundStep((int) $value),
            set: fn (mixed $value): ?int => $value === null ? null : $this->boundStep((int) $value),
        );
    }

    private function boundStep(int $step): int
    {
        return max(0, min(self::LAST_STEP, $step));
    }
}
