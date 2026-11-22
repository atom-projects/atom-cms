<?php

namespace App\Http\Middleware;

use App\Support\WebsiteSettingRules;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ConfigureRuntimeFilesystems
{
    public function handle(Request $request, Closure $next): Response
    {
        $this->configure('badges', 'badge_path_filesystem', '/var/www/gamedata/c_images/album1584');
        $this->configure('ads', 'ads_path_filesystem', '/var/www/gamedata/custom');

        Storage::forgetDisk(['badges', 'ads']);

        return $next($request);
    }

    /**
     * A disk whose folder setting is empty or refused gets no driver, so its
     * operations fail instead of using another folder.
     */
    private function configure(string $disk, string $key, string $default): void
    {
        $root = WebsiteSettingRules::hotelPath($key, $default);

        Config::set("filesystems.disks.{$disk}", $root === null ? null : ['driver' => 'local', 'root' => $root]);
    }
}
