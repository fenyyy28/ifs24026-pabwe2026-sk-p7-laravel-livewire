<?php

namespace Tests\Feature\Admin;

use App\Features\Admin\Livewire\AdminUserStockList;
use App\Features\Admin\Livewire\UserList;
use App\Features\Admin\Services\AdminUserService;
use App\Features\Stock\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_ringkasan_user(): void
    {
        $member = $this->makeMember(['name' => 'Budi']);
        Stock::factory()->count(2)->for($member)->create(['quantity' => 10]);
        $this->makeMember(['name' => 'Cici']);

        $service = app(AdminUserService::class);
        $users = $service->paginateUsers();

        $budi = $users->firstWhere('name', 'Budi');
        $this->assertSame(2, $budi->stocks_count);
        $this->assertSame(20, (int) $budi->stocks_quantity_sum);

        $this->assertSame(1, $service->paginateUsers('Cici')->total());
        $this->assertSame(1, $service->paginateUsers('budi')->total());
        $this->assertSame(2, $service->paginateStocksOf($member)->total());
    }

    public function test_user_list(): void
    {
        $admin = $this->makeAdmin(['name' => 'Bos']);
        $this->makeMember(['name' => 'Budi Santoso', 'username' => 'budis']);
        $this->makeMember(['name' => 'Cici Lestari', 'username' => 'cicil']);

        $this->actingAs($admin)->get('/admin/users')->assertOk()->assertSeeLivewire(UserList::class);

        Livewire::actingAs($admin)->test(UserList::class)
            ->assertSee('Budi Santoso')->assertSee('Cici Lestari')
            ->set('search', 'cicil')
            ->assertSee('Cici Lestari')->assertDontSee('Budi Santoso')
            ->set('search', 'tidak-ada')
            ->assertSee('Tidak ada user.');
    }

    public function test_daftar_stok_user_read_only(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember(['name' => 'Budi']);
        Stock::factory()->for($member)->create(['name' => 'Kertas Budi', 'sku' => 'KB-1']);
        Stock::factory()->for($member)->lowStock()->create(['name' => 'Pulpen Budi']);
        Stock::factory()->create(['name' => 'Stok Orang Lain']);

        $this->actingAs($admin)->get("/admin/users/{$member->id}/stocks")
            ->assertOk()->assertSeeLivewire(AdminUserStockList::class);

        Livewire::actingAs($admin)->test(AdminUserStockList::class, ['user' => $member])
            ->assertSee('Kertas Budi')->assertSee('Pulpen Budi')
            ->assertDontSee('Stok Orang Lain')
            ->assertDontSee('Hapus')->assertDontSee('Edit')
            ->set('search', 'Kertas')
            ->assertSee('Kertas Budi')->assertDontSee('Pulpen Budi')
            ->set('search', '')
            ->set('status', 'low_stock')
            ->assertSee('Pulpen Budi')->assertDontSee('Kertas Budi');
    }

    public function test_user_tanpa_stok(): void
    {
        $member = $this->makeMember();

        Livewire::actingAs($this->makeAdmin())->test(AdminUserStockList::class, ['user' => $member])
            ->assertSee('User ini belum punya stok.');
    }

    public function test_komponen_admin_tidak_memiliki_aksi_ubah(): void
    {
        $this->assertFalse(method_exists(AdminUserStockList::class, 'delete'));
        $this->assertFalse(method_exists(AdminUserStockList::class, 'save'));
        $this->assertFalse(method_exists(AdminUserStockList::class, 'adjust'));
    }
}
