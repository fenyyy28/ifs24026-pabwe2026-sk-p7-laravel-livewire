<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function makeMember(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function makeAdmin(array $attributes = []): User
    {
        return User::factory()->admin()->create($attributes);
    }
}
