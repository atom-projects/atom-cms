<?php

namespace Tests;

abstract class PlusTestCase extends TestCase
{
    protected function databaseName(): string
    {
        return (string) env('DB_PLUS_DATABASE', 'testing_plus');
    }

    protected function emulatorDriver(): string
    {
        return 'plus';
    }

    protected function fakesRcon(): bool
    {
        return false;
    }
}
