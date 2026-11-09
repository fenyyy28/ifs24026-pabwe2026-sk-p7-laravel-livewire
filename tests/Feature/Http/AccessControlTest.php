<?php

namespace Tests\Feature\Http;

use App\Features\Stock\Models\Stock;
use App\Http\Middleware\EnsureRole;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirect_sesuai_peran(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->actingAs($this->makeAdmin())->get('/')->assertRedirect('/admin/users');
        $this->actingAs($this->makeMember())->get('/')->assertRedirect('/stocks');
    }

    public function test_guest_ditolak(): void
    {
        $stock = Stock::factory()->create();

        $this->get('/stocks')->assertRedirect('/login');
        $this->get("/stocks/{$stock->id}")->assertRedirect('/login');
        $this->get('/admin/users')->assertRedirect('/login');
        $this->post('/logout')->assertRedirect('/login');
    }

    public function test_user_login_tidak_bisa_membuka_login_lagi(): void
    {
        $this->actingAs($this->makeMember())->get('/login')->assertRedirect('/');
    }

    public function test_member_tidak_bisa_membuka_stok_orang_lain(): void
    {
        $member = $this->makeMember();
        $foreign = Stock::factory()->create();
        $own = Stock::factory()->for($member)->create();

        $this->actingAs($member)->get("/stocks/{$foreign->id}")->assertForbidden();
        $this->actingAs($member)->get("/stocks/{$own->id}")->assertOk();
    }

    public function test_member_tidak_bisa_masuk_area_admin(): void
    {
        $member = $this->makeMember();

        $this->actingAs($member)->get('/admin/users')->assertForbidden();
        $this->actingAs($member)->get("/admin/users/{$member->id}/stocks")->assertForbidden();
    }

    public function test_admin_tidak_bisa_masuk_area_stok_member(): void
    {
        $admin = $this->makeAdmin();
        $stock = Stock::factory()->create();

        $this->actingAs($admin)->get('/stocks')->assertForbidden();
        $this->actingAs($admin)->get("/stocks/{$stock->id}")->assertForbidden();
    }

    public function test_ensure_role_menolak_tanpa_user(): void
    {
        $this->expectException(HttpException::class);

        (new EnsureRole)->handle(Request::create('/'), fn () => response('ok'), 'admin');
    }

    public function test_seeder_membuat_akun_dan_contoh_stok(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', ['username' => 'admin', 'role' => 'admin']);
        $member = \App\Models\User::where('username', 'member')->first();
        $this->assertSame('member', $member->role);
        $this->assertSame(5, $member->stocks()->count());
    }
}
