<?php

namespace App\Data;

use App\Models\User;
use App\Services\Avatar\AvatarImager;

final readonly class PublicUserData
{
    public const COLUMNS = ['id', 'username', 'motto', 'look', 'online'];

    public function __construct(public int $id, public string $username, public string $motto, public string $look, public ?string $avatar, public bool $online) {}

    public static function from(User $user): self
    {
        return new self((int) $user->id, $user->username, (string) $user->motto, $user->look, app(AvatarImager::class)->apiPrefix($user->look), (bool) $user->online);
    }
}
