<?php

namespace App\Filament\Tables\Columns;

use Filament\Tables\Columns\Column;

class UserAvatarColumn extends Column
{
    /** @var array<string, mixed> */
    protected array $avatarOptions = [];

    protected ?string $figurePointer = null;

    protected string $view = 'filament.tables.columns.user-avatar';

    public function getAvatarUrl(): string
    {
        return avatar_url((string) data_get($this->getRecord(), $this->figurePointer ?? 'look', ''), $this->avatarOptions);
    }

    /** @param  array<string, mixed>  $avatarOptions  See AvatarImager::url(). */
    public function options(array $avatarOptions): UserAvatarColumn
    {
        $this->avatarOptions = $avatarOptions;

        return $this;
    }

    /**
     * Used to reference the user's figure string through relationships in a Laravel model. By default it will take the look property of the main class.
     */
    public function pointer(string $figurePointer): UserAvatarColumn
    {
        $this->figurePointer = $figurePointer;

        return $this;
    }
}
