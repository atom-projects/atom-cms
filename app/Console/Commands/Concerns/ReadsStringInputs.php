<?php

namespace App\Console\Commands\Concerns;

trait ReadsStringInputs
{
    /**
     * A required argument's value, or an empty string when it is not a string.
     */
    private function stringArgument(string $name): string
    {
        $value = $this->argument($name);

        return is_string($value) ? $value : '';
    }

    /**
     * The option's value when the caller passed a string, otherwise null.
     */
    private function stringOption(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) ? $value : null;
    }
}
