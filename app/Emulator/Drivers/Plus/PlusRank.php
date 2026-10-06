<?php

namespace App\Emulator\Drivers\Plus;

use App\Emulator\Models\Rank;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class PlusRank extends Rank
{
    protected $table = 'roles';

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id')
            ->where(fn ($query) => $query->whereNull('user_roles.expires_at')->orWhere('user_roles.expires_at', '>', now('UTC')->format('Y-m-d H:i:s.u')));
    }

    /** @return Attribute<string, never> */
    protected function rankName(): Attribute
    {
        return Attribute::get(fn (): string => (string) $this->getAttribute('name'));
    }

    /** @return Attribute<string, never> */
    protected function badge(): Attribute
    {
        return Attribute::get(fn (): string => (string) $this->getAttribute('badge_code'));
    }

    /** @return Attribute<string, never> */
    protected function staffColor(): Attribute
    {
        return Attribute::get(fn (): string => '');
    }

    /** @return Attribute<string, never> */
    protected function staffBackground(): Attribute
    {
        return Attribute::get(fn (): string => '');
    }

    /** @return Attribute<string, never> */
    protected function jobDescription(): Attribute
    {
        return Attribute::get(fn (): string => (string) $this->getAttribute('description'));
    }
}
