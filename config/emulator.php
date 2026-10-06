<?php

use App\Emulator\Drivers\Ada\AdaDriver;
use App\Emulator\Drivers\Arcturus\ArcturusDriver;
use App\Emulator\Drivers\Plus\PlusDriver;

return [

    /*
    |--------------------------------------------------------------------------
    | Emulator driver
    |--------------------------------------------------------------------------
    |
    | The emulator whose database schema this hotel runs on. Each driver maps
    | the CMS's domain concepts (currency, stats, badges, bans, furniture)
    | onto that emulator's own tables and columns.
    |
    */

    'driver' => env('EMULATOR_DRIVER', 'arcturus'),

    /*
    |--------------------------------------------------------------------------
    | Emulator drivers
    |--------------------------------------------------------------------------
    |
    | Each class owns its repositories, schema migrations, feature support,
    | validation constraints, installer behavior, relations, and RCON bridge.
    | Adding another emulator only requires an EmulatorDriver implementation
    | and one registry entry here; shared application code remains unchanged.
    |
    */

    'drivers' => [

        'arcturus' => ArcturusDriver::class,
        'ada' => AdaDriver::class,
        'plus' => PlusDriver::class,

    ],

];
