<?php

namespace App\Rules;

use App\Models\User;
use App\Services\Auth\PasswordVerifier;
use Closure;
use Illuminate\Contracts\Validation\InvokableRule;
use Illuminate\Support\Facades\Auth;

class CurrentPasswordRule implements InvokableRule
{
    /**
     * Run the validation rule.
     */
    public function __invoke(string $attribute, mixed $value, Closure $fail): void
    {
        $user = Auth::user();

        if (! $user instanceof User || ! is_string($value) || ! app(PasswordVerifier::class)->verify($user, $value)) {
            $fail('It seems like your current password is wrong.');
        }
    }
}
