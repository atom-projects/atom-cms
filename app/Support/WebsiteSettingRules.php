<?php

namespace App\Support;

use App\Enums\WebsiteSettingFormat;
use App\Services\SettingsService;
use Closure;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Knows, per website setting key, what a valid value is and whether only the
 * holder of the sensitive settings permission may change it. Keys that are
 * not listed accept any value and are not sensitive.
 */
final class WebsiteSettingRules
{
    public const SENSITIVE_PERMISSION = 'manage_sensitive_website_settings';

    /** @var array<string, WebsiteSettingFormat> */
    private const FORMATS = [
        'nitro_path' => WebsiteSettingFormat::ClientUrl,
        'housekeeping_url' => WebsiteSettingFormat::Link,
        'discord_invitation_link' => WebsiteSettingFormat::Link,
        'avatar_imager' => WebsiteSettingFormat::Link,
        'cms_logo' => WebsiteSettingFormat::Link,
        'cms_header' => WebsiteSettingFormat::Link,
        'cms_me_backdrop' => WebsiteSettingFormat::Link,
        'badges_path' => WebsiteSettingFormat::Link,
        'group_badge_path' => WebsiteSettingFormat::Link,
        'room_thumbnail_path' => WebsiteSettingFormat::Link,
        'furniture_icons_path' => WebsiteSettingFormat::Link,
        'catalog_icons_path' => WebsiteSettingFormat::Link,
        'ads_picture_path' => WebsiteSettingFormat::Link,
        'flash_external_texts_file' => WebsiteSettingFormat::HotelFile,
        'nitro_external_texts_file' => WebsiteSettingFormat::HotelFile,
        'badge_path_filesystem' => WebsiteSettingFormat::HotelFolder,
        'ads_path_filesystem' => WebsiteSettingFormat::HotelFolder,
        'theme' => WebsiteSettingFormat::Theme,
        'rcon_ip' => WebsiteSettingFormat::Host,
        'rcon_port' => WebsiteSettingFormat::Port,
        'force_staff_2fa' => WebsiteSettingFormat::Toggle,
        'maintenance_enabled' => WebsiteSettingFormat::Toggle,
        'google_recaptcha_enabled' => WebsiteSettingFormat::Toggle,
        'cloudflare_turnstile_enabled' => WebsiteSettingFormat::Toggle,
        'vpn_block_enabled' => WebsiteSettingFormat::Toggle,
        'requires_beta_code' => WebsiteSettingFormat::Toggle,
        'disable_registration' => WebsiteSettingFormat::Toggle,
        'website_wordfilter_enabled' => WebsiteSettingFormat::Toggle,
        'min_staff_rank' => WebsiteSettingFormat::Rank,
        'min_housekeeping_rank' => WebsiteSettingFormat::Rank,
        'min_rank_to_see_hidden_staff' => WebsiteSettingFormat::Rank,
        'min_maintenance_login_rank' => WebsiteSettingFormat::Rank,
        'max_accounts_per_ip' => WebsiteSettingFormat::Count,
    ];

    /** Keys that decide where tickets, files and RCON commands go, and who must use two-factor. */
    private const SENSITIVE = [
        'nitro_path',
        'flash_external_texts_file',
        'nitro_external_texts_file',
        'badge_path_filesystem',
        'ads_path_filesystem',
        'rcon_ip',
        'rcon_port',
        'force_staff_2fa',
        'min_staff_rank',
    ];

    /** Characters that could leave an HTML attribute or CSS url(), or change how a URL is parsed. */
    private const UNSAFE_URL_CHARACTERS = '/[\x00-\x20"\'()<>\\\\`]/';

    public static function isSensitive(string $key): bool
    {
        return in_array($key, self::SENSITIVE, true);
    }

    public static function accepts(string $key, ?string $value): bool
    {
        $format = self::FORMATS[$key] ?? null;
        $value ??= '';

        return match ($format) {
            null => true,
            WebsiteSettingFormat::ClientUrl => self::isSitePath($value) || in_array(self::origin($value), self::clientOrigins(), true),
            WebsiteSettingFormat::Link => $value === '' || self::isSitePath($value) || self::isPublicUrl($value),
            WebsiteSettingFormat::HotelFile => $value === '' || self::isInsideHotelFileRoots($value),
            WebsiteSettingFormat::HotelFolder => self::isInsideHotelFileRoots($value),
            WebsiteSettingFormat::Theme => in_array($value, self::themes(), true),
            WebsiteSettingFormat::Host => filter_var($value, FILTER_VALIDATE_IP) !== false
                || filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false,
            WebsiteSettingFormat::Port => preg_match('/^\d{1,5}$/D', $value) === 1 && (int) $value >= 1 && (int) $value <= 65535,
            WebsiteSettingFormat::Toggle => in_array($value, ['0', '1'], true),
            WebsiteSettingFormat::Rank => preg_match('/^[1-9]\d{0,9}$/D', $value) === 1 && (int) $value <= 2147483647,
            WebsiteSettingFormat::Count => preg_match('/^\d{1,10}$/D', $value) === 1 && (int) $value <= 2147483647,
        };
    }

    /**
     * @throws ValidationException
     */
    public static function validate(string $key, ?string $value): void
    {
        if (! self::accepts($key, $value)) {
            throw ValidationException::withMessages([$key => self::message($key)]);
        }
    }

    /**
     * The stored path of a file or folder setting, or null when it is empty or
     * fails its rule. Every file read, write or delete through these settings
     * goes through here, so a value stored before the rule existed is refused
     * and logged instead of used.
     */
    public static function hotelPath(string $key, ?string $default = null): ?string
    {
        $path = app(SettingsService::class)->getOrDefault($key, $default);

        if (! is_string($path) || $path === '') {
            return null;
        }

        if (! self::accepts($key, $path)) {
            self::reportRefused($key, $path);

            return null;
        }

        return $path;
    }

    /**
     * Log, at most once per request, that a stored value fails its rule, so
     * the operator can find the cause from the log.
     */
    public static function reportRefused(string $key, string $value): void
    {
        once(fn () => Log::error(sprintf(
            'Atom refused the stored %s setting "%s". %s APP_URL is %s.',
            $key,
            $value,
            self::message($key),
            (string) config('app.url'),
        )));
    }

    /**
     * A validation rule for the value of the given key.
     */
    public static function rule(string $key): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($key): void {
            if (! is_scalar($value) && $value !== null) {
                $fail(self::message($key));

                return;
            }

            if (! self::accepts($key, $value === null ? null : (string) $value)) {
                $fail(self::message($key));
            }
        };
    }

    /**
     * The origins the game client may load from: this site, the headless
     * frontend (ATOM_FRONTEND_URL) and NITRO_ALLOWED_ORIGINS.
     *
     * @return list<string>
     */
    public static function clientOrigins(): array
    {
        $origins = [self::origin((string) config('app.url')), self::origin((string) config('atom.frontend_url'))];

        foreach ((array) config('habbo.client.nitro_allowed_origins', []) as $origin) {
            $origins[] = self::origin((string) $origin);
        }

        return array_values(array_unique(array_filter($origins)));
    }

    private static function message(string $key): string
    {
        return match (self::FORMATS[$key] ?? null) {
            WebsiteSettingFormat::ClientUrl => 'Use a path on this site, such as /client/nitro, or a URL on APP_URL, ATOM_FRONTEND_URL or an origin listed in NITRO_ALLOWED_ORIGINS.',
            WebsiteSettingFormat::Link => 'Use a path on this site, such as /assets/images/logo.png, or an https:// URL.',
            WebsiteSettingFormat::HotelFile, WebsiteSettingFormat::HotelFolder => 'Use an absolute path inside one of these folders (HOTEL_FILE_ROOTS): ' . implode(', ', self::hotelFileRoots()) . '.',
            WebsiteSettingFormat::Theme => 'Use one of the installed themes: ' . implode(', ', self::themes()) . '.',
            WebsiteSettingFormat::Host => 'Use an IP address or a host name.',
            WebsiteSettingFormat::Port => 'Use a port from 1 to 65535.',
            WebsiteSettingFormat::Toggle => 'Use 0 or 1.',
            WebsiteSettingFormat::Rank => 'Use a rank of 1 or higher.',
            WebsiteSettingFormat::Count => 'Use a whole number of 0 or more.',
            null => 'This value is not valid.',
        };
    }

    /**
     * A path on this site: one leading slash, so the browser cannot read it as another host.
     */
    private static function isSitePath(string $value): bool
    {
        return str_starts_with($value, '/')
            && ! str_starts_with($value, '//')
            && preg_match(self::UNSAFE_URL_CHARACTERS, $value) === 0;
    }

    private static function isPublicUrl(string $value): bool
    {
        $origin = self::origin($value);

        return $origin !== null && (str_starts_with($origin, 'https://') || $origin === self::origin((string) config('app.url')));
    }

    /**
     * The lowercase scheme://host[:port] of an http(s) URL, or null when the value is not one.
     */
    private static function origin(string $url): ?string
    {
        if (preg_match(self::UNSAFE_URL_CHARACTERS, $url) === 1 || preg_match('#^https?://#i', $url) !== 1) {
            return null;
        }

        $parts = parse_url($url);

        if (! is_array($parts) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $defaultPort = $scheme === 'https' ? 443 : 80;
        $port = isset($parts['port']) && $parts['port'] !== $defaultPort ? ':' . $parts['port'] : '';

        return $scheme . '://' . strtolower($parts['host']) . $port;
    }

    private static function isInsideHotelFileRoots(string $path): bool
    {
        $isAbsolute = str_starts_with($path, '/') || preg_match('#^[A-Za-z]:[\\\\/]#', $path) === 1;

        if (! $isAbsolute || str_contains($path, "\0") || preg_match('#(^|[\\\\/])\.\.([\\\\/]|$)#', $path) === 1) {
            return false;
        }

        $resolved = self::resolve($path);

        foreach (self::hotelFileRoots() as $root) {
            $root = self::resolve($root);

            if ($root !== '' && ($resolved === $root || str_starts_with($resolved, $root . '/'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve symbolic links in the part of the path that exists, so a link
     * inside a root cannot point outside it. Missing parts are kept as written.
     */
    private static function resolve(string $path): string
    {
        $normalized = rtrim(str_replace('\\', '/', $path), '/');
        $existing = $normalized;
        $missing = [];

        while (($real = realpath($existing === '' ? '/' : $existing)) === false) {
            $parent = dirname($existing);

            if ($parent === $existing || $parent === '.') {
                return $normalized;
            }

            array_unshift($missing, basename($existing));
            $existing = $parent;
        }

        $real = rtrim(str_replace('\\', '/', $real), '/');

        return $missing === [] ? $real : $real . '/' . implode('/', $missing);
    }

    /** @return list<string> */
    private static function hotelFileRoots(): array
    {
        return array_values(array_filter(array_map('strval', (array) config('habbo.hotel_file_roots', []))));
    }

    /** @return list<string> */
    private static function themes(): array
    {
        return array_values(array_map('basename', File::directories((string) config('theme.base_path'))));
    }
}
