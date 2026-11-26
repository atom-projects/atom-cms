<?php

// Runs the folder rule in its own process, because open_basedir can only be
// narrowed once set and would break every later test in the suite.

use App\Support\WebsiteSettingRules;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$root = $argv[1];
config(['habbo.hotel_file_roots' => [$root]]);

echo json_encode([
    'inside' => WebsiteSettingRules::accepts('badge_path_filesystem', $root . '/badges'),
    'outside' => WebsiteSettingRules::accepts('badge_path_filesystem', $root . '-outside'),
]);
