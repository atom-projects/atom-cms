<?php

namespace App\Services\Avatar;

/**
 * Builds the address of an avatar image. With a secret it points at the
 * hotel's own imager, which draws avatars with the client's renderer. Without
 * one it keeps using the imager address from the website settings.
 */
final readonly class AvatarImager
{
    public const DEFAULT_FIGURE = 'hd-180-1.ch-210-66.lg-270-82.sh-290-80';

    private const MAX_FIGURE_LENGTH = 240;

    private const SIZES = ['s' => 's', 'm' => 'm', 'l' => 'l', 'b' => 'm'];

    private const POSTURES = ['sit' => 'sit', 'lay' => 'lay', 'wlk' => 'wlk'];

    private const EXPRESSIONS = ['wav' => 'wave', 'kiss' => 'kiss', 'laugh' => 'laugh'];

    private const GESTURES = ['sml', 'sad', 'agr', 'srp'];

    public function __construct(
        private ?string $secret,
        private string $baseUrl,
        private int $version,
        private string $officialUrl,
        private string $defaultFigure,
    ) {}

    public function isSelfHosted(): bool
    {
        return $this->secret !== null && $this->secret !== '';
    }

    /**
     * Options: direction, head_direction, gesture, action (wav, sit, lay,
     * wlk, kiss, laugh), size (s, m, l), headonly, dance, effect, item and
     * animated.
     *
     * @param  array<string, mixed>  $options
     */
    public function url(?string $figure, array $options = [], ?string $gender = null): string
    {
        if (! $this->isSelfHosted()) {
            return $this->officialUrl . rawurlencode((string) $figure) . $this->officialQuery($options, $gender);
        }

        return sprintf('%s.%s.png', $this->prefix($figure), $this->variant($options, $gender));
    }

    /**
     * The signed part of a self-hosted address. A client adds ".<variant>.png"
     * to ask for any variant of this figure.
     */
    public function prefix(?string $figure): ?string
    {
        if (! $this->isSelfHosted()) {
            return null;
        }

        $figure = $this->cleanFigure($figure);
        $signature = substr(hash_hmac('sha256', "v{$this->version}/{$figure}", (string) $this->secret), 0, 16);

        return sprintf('%s/v%d/%s/%s', rtrim($this->baseUrl, '/'), $this->version, $figure, $signature);
    }

    public function usesOfficialSizes(): bool
    {
        return $this->isSelfHosted() || str_contains($this->officialUrl, 'www.habbo.com');
    }

    private function cleanFigure(?string $figure): string
    {
        $figure = (string) preg_replace('/\.{2,}/', '.', (string) preg_replace('/[^a-z0-9.-]/', '', strtolower((string) $figure)));
        $figure = trim($figure, '.-');

        return $figure === '' || strlen($figure) > self::MAX_FIGURE_LENGTH ? $this->defaultFigure : $figure;
    }

    /** @param  array<string, mixed>  $options */
    private function variant(array $options, ?string $gender): string
    {
        $head = (bool) ($options['headonly'] ?? false);
        $direction = $this->within($options['direction'] ?? null, 7, 2);
        $headDirection = $this->within($options['head_direction'] ?? null, 7, $direction);
        $action = (string) ($options['action'] ?? '');
        $gesture = in_array($options['gesture'] ?? null, self::GESTURES, true) ? $options['gesture'] : 'nrm';

        if ($head) {
            return implode('-', [$this->gender($gender), $this->size($options), 'head', $headDirection, $headDirection, 'std', 'none', $gesture, 0, 0, 0, 'still']);
        }

        return implode('-', [
            $this->gender($gender),
            $this->size($options),
            'full',
            $direction,
            $headDirection,
            self::POSTURES[$action] ?? 'std',
            self::EXPRESSIONS[$action] ?? 'none',
            $gesture,
            $this->within($options['dance'] ?? null, 4, 0),
            $this->within($options['effect'] ?? null, 99999, 0),
            $this->within($options['item'] ?? null, 99999, 0),
            ($options['animated'] ?? false) ? 'anim' : 'still',
        ]);
    }

    /** @param  array<string, mixed>  $options */
    private function officialQuery(array $options, ?string $gender): string
    {
        $query = '';

        foreach (['direction', 'head_direction', 'gesture', 'action', 'size'] as $name) {
            if (isset($options[$name]) && is_scalar($options[$name])) {
                $query .= sprintf('&%s=%s', $name, rawurlencode((string) $options[$name]));
            }
        }

        if ($options['headonly'] ?? false) {
            $query .= '&headonly=1';
        }

        foreach (['dance', 'effect'] as $name) {
            if ($this->within($options[$name] ?? null, 99999, 0) > 0) {
                $query .= sprintf('&%s=%d', $name, $options[$name]);
            }
        }

        if ($gender !== null && $gender !== '') {
            $query .= '&gender=' . rawurlencode($gender);
        }

        return $query . (($options['animated'] ?? false) ? '&img_format=gif' : '');
    }

    private function gender(?string $gender): string
    {
        return strtolower((string) $gender) === 'f' ? 'f' : 'm';
    }

    /** @param  array<string, mixed>  $options */
    private function size(array $options): string
    {
        $size = $options['size'] ?? null;

        return is_string($size) ? (self::SIZES[$size] ?? 'm') : 'm';
    }

    private function within(mixed $value, int $max, int $default): int
    {
        return is_numeric($value) && (int) $value == $value && $value >= 0 && $value <= $max ? (int) $value : $default;
    }
}
