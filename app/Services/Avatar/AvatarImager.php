<?php

namespace App\Services\Avatar;

use RuntimeException;

final readonly class AvatarImager
{
    public function __construct(
        private ?string $secret,
        private string $baseUrl,
        private int $version,
        private string $officialUrl,
        private string $defaultFigure,
    ) {}

    public function isSelfHosted(): bool
    {
        throw new RuntimeException('Not implemented.');
    }

    /** @param  array<string, mixed>  $options */
    public function url(?string $figure, array $options = [], ?string $gender = null): string
    {
        throw new RuntimeException('Not implemented.');
    }

    public function prefix(?string $figure): ?string
    {
        throw new RuntimeException('Not implemented.');
    }

    public function usesOfficialSizes(): bool
    {
        throw new RuntimeException('Not implemented.');
    }
}
