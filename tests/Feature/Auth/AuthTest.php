<?php

namespace Tests\Feature\Auth;

use App\Features\Auth\Actions\LoginUserAction;
use App\Features\Auth\Actions\RegisterUserAction;
use App\Features\Auth\Livewire\Login;
use App\Features\Auth\Livewire\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_login_memiliki_id_yang_diwajibkan(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSeeLivewire(Login::class)
            ->assertSee('id="login-username"', false)
            ->assertSee('id="login-password"', false)
            ->assertSee('id="login-submit"', false);
    }

    public function test_halaman_register(): void
    {
        $this->get('/register')->assertOk()->assertSeeLivewire(Register::class);
    }

    public function test_login_berhasil(): void
    {
        $user = $this->makeMember(['username' => 'budi', 'password' => 'rahasia']);

        Livewire::test(Login::class)
            ->set('username', 'budi')
            ->set('password', 'rahasia')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_gagal(): void
    {
        $this->makeMember(['username' => 'budi', 'password' => 'rahasia']);

        Livewire::test(Login::class)
            ->set('username', 'budi')
            ->set('password', 'salah')
            ->call('login')
            ->assertHasErrors('username')
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_login_validasi(): void
    {
        Livewire::test(Login::class)->call('login')
            ->assertHasErrors(['username' => 'required', 'password' => 'required']);
    }

    public function test_login_action(): void
    {
        $this->makeMember(['username' => 'siti', 'password' => 'abc12345']);
        $action = new LoginUserAction;

        $this->assertFalse($action->execute('siti', 'salah'));
        $this->assertTrue($action->execute('siti', 'abc12345', true));
    }

    public function test_register_berhasil_selalu_member(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Andi')
            ->set('username', 'andi')
            ->set('password', 'rahasia')
            ->set('password_confirmation', 'rahasia')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $user = User::where('username', 'andi')->first();
        $this->assertSame(User::ROLE_MEMBER, $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_register_validasi(): void
    {
        $this->makeMember(['username' => 'dipakai']);

        Livewire::test(Register::class)
            ->set('name', 'X')
            ->set('username', 'dipakai')
            ->set('password', '123')
            ->set('password_confirmation', 'beda')
            ->call('register')
            ->assertHasErrors(['username' => 'unique', 'password']);
    }

    public function test_register_action(): void
    {
        $user = (new RegisterUserAction)->execute('Rina', 'rina', 'rahasia');

        $this->assertSame('member', $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_logout(): void
    {
        $this->actingAs($this->makeMember())
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }
}
