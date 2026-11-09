<?php

namespace App\Features\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RegisterUserAction
{
    /**
     * Registrasi publik selalu membuat akun berperan "member".
     */
    public function execute(string $name, string $username, string $password): User
    {
        $user = User::create([
            'name' => $name,
            'username' => $username,
            'password' => $password,
            'role' => User::ROLE_MEMBER,
        ]);

        Auth::login($user);
        session()->regenerate();

        return $user;
    }
}
