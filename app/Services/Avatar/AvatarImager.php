<?php

namespace App\Services\Avatar;

final readonly class AvatarImager
{
    private const DEFAULT_DIRECTION = 2;

    private const SIZES = ['s' => 's', 'l' => 'l'];

    private const GESTURES = ['nrm', 'sml', 'sad', 'agr', 'srp'];

    private const POSTURES = ['std', 'sit', 'lay', 'wlk'];

    private const EXPRESSIONS = ['wav' => 'wave', 'blw' => 'kiss', 'lgh' => 'laugh'];

    /**
     * @param  array<string, mixed>  $query
     */
    public function signedUrl(string $figure, string $gender, array $query): string
    {
        $version = (int) config('habbo.avatar_imager.version');
        $variant = $this->variant($gender, $query);
        $signature = substr(hash_hmac('sha256', "v{$version}/{$figure}/{$variant}", (string) config('habbo.avatar_imager.secret')), 0, 16);

        $base = rtrim((string) config('habbo.avatar_imager.url'), '/');
        $base = str_starts_with($base, '/') ? url($base) : $base;

        return "{$base}/v{$version}/{$figure}/{$signature}.{$variant}.png";
    }

    public function isDrawable(string $figure): bool
    {
        return preg_match('/^[a-z0-9][a-z0-9.-]{0,239}$/', $figure) === 1
            && ! str_contains($figure, '..')
            && ! str_ends_with($figure, '.')
            && ! str_ends_with($figure, '-');
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function variant(string $gender, array $query): string
    {
        $size = self::SIZES[$this->text($query, 'size')] ?? 'm';
        $gesture = $this->text($query, 'gesture');
        $gesture = in_array($gesture, self::GESTURES, true) ? $gesture : 'nrm';
        $direction = $this->direction($query, 'direction');
        $headDirection = $this->direction($query, 'head_direction');
        $posture = 'std';
        $expression = 'none';
        $handItem = null;
        $waves = false;

        foreach (explode(',', strtolower($this->text($query, 'action'))) as $token) {
            if (in_array($token, self::POSTURES, true)) {
                $posture = $token;
            } elseif (isset(self::EXPRESSIONS[$token])) {
                $expression = self::EXPRESSIONS[$token];
                $waves = $waves || $token === 'wav';
            } elseif (preg_match('/^crr=(\d{1,5})$/', $token, $match) === 1) {
                $handItem = (int) $match[1];
            }
        }

        // Habbo draws a waving avatar as a whole body even when a head is asked for.
        $headOnly = $this->text($query, 'headonly') === '1' && ! ($waves && $handItem === null);

        if ($headOnly) {
            return "{$gender}-{$size}-head-{$headDirection}-{$headDirection}-std-none-{$gesture}-0-0-0-still";
        }

        // On a body facing 4, 5 or 6, Habbo draws head directions 0, 1 and 2 as 6, 5 and 4.
        if ($direction >= 4 && $direction <= 6 && $headDirection <= 2) {
            $headDirection = 6 - $headDirection;
        }

        return implode('-', [$gender, $size, 'full', $direction, $headDirection, $posture, $expression, $gesture, 0, 0, $handItem ?? 0, 'still']);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function direction(array $query, string $key): int
    {
        $value = $this->text($query, $key);

        return ctype_digit($value) ? (int) substr($value, -3) % 8 : self::DEFAULT_DIRECTION;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function text(array $query, string $key): string
    {
        $value = $query[$key] ?? '';

        return is_string($value) ? $value : '';
    }
}
