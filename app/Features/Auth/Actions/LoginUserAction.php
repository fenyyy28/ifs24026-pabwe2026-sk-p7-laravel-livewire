<?php

namespace App\Features\Auth\Actions;

use Illuminate\Support\Facades\Auth;

class LoginUserAction
{
    public function execute(string $username, string $password, bool $remember = false): bool
    {
        $success = Auth::attempt(['username' => $username, 'password' => $password], $remember);

        if ($success) {
            session()->regenerate();
        }

        return $success;
    }
}
