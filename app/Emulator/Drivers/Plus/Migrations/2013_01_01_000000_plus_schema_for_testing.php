<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! app()->environment('testing') || Schema::hasTable('users')) {
            return;
        }

        $path = base_path('tests/Fixtures/plus-schema.sql');

        if (! is_readable($path)) {
            throw new RuntimeException('Unable to read the PlusEMU test schema.');
        }

        $file = new SplFileObject($path);
        $statement = '';

        foreach ($file as $line) {
            if (! is_string($line)) {
                continue;
            }

            $statement .= $line;

            if (! str_ends_with(rtrim($line), ';')) {
                continue;
            }

            DB::connection()->getPdo()->exec($statement);
            $statement = '';
        }

        if (trim($statement) !== '') {
            throw new RuntimeException('The PlusEMU test schema ends with an incomplete statement.');
        }
    }

    public function down(): void {}
};
