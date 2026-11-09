<?php

namespace Tests\Feature\Stock;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Policies\StockPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class StockPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_pemilik_boleh_semua_aksi(): void
    {
        $user = $this->makeMember();
        $stock = Stock::factory()->for($user)->create();
        $policy = new StockPolicy;

        $this->assertTrue($policy->viewAny($user));
        $this->assertTrue($policy->create($user));
        $this->assertTrue($policy->view($user, $stock));
        $this->assertTrue($policy->update($user, $stock));
        $this->assertTrue($policy->delete($user, $stock));
    }

    public function test_bukan_pemilik_ditolak(): void
    {
        $stock = Stock::factory()->create();
        $other = $this->makeMember();

        $this->assertFalse(Gate::forUser($other)->allows('view', $stock));
        $this->assertFalse(Gate::forUser($other)->allows('update', $stock));
        $this->assertFalse(Gate::forUser($other)->allows('delete', $stock));
    }

    public function test_admin_tidak_boleh_mengubah_stok_user_lain(): void
    {
        $admin = $this->makeAdmin();
        $stock = Stock::factory()->create();
        $policy = new StockPolicy;

        $this->assertFalse($policy->viewAny($admin));
        $this->assertFalse($policy->create($admin));
        $this->assertFalse(Gate::forUser($admin)->allows('update', $stock));
        $this->assertFalse(Gate::forUser($admin)->allows('delete', $stock));
    }
}
