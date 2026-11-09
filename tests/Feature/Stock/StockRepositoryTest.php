<?php

namespace Tests\Feature\Stock;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Repositories\StockRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginate_hanya_milik_user_dengan_filter(): void
    {
        $repo = new StockRepository;
        $user = $this->makeMember();
        $other = $this->makeMember();

        Stock::factory()->for($user)->create(['name' => 'Kertas', 'sku' => 'KRT-1']);
        Stock::factory()->for($user)->lowStock()->create(['name' => 'Pulpen', 'sku' => 'PLP-1']);
        Stock::factory()->for($other)->create(['name' => 'Milik Orang Lain']);

        $this->assertSame(2, $repo->paginateForUser($user)->total());
        $this->assertSame(1, $repo->paginateForUser($user, 'kert')->total());
        $this->assertSame(1, $repo->paginateForUser($user, 'PLP-1')->total());
        $this->assertSame(1, $repo->paginateForUser($user, null, 'low_stock')->total());
        $this->assertSame(0, $repo->paginateForUser($user, 'Orang Lain')->total());
    }

    public function test_pagination_per_halaman(): void
    {
        $user = $this->makeMember();
        Stock::factory()->count(12)->for($user)->create();

        $page = (new StockRepository)->paginateForUser($user, null, null, 10);

        $this->assertCount(10, $page->items());
        $this->assertSame(12, $page->total());
    }

    public function test_all_for_user_create_update_delete(): void
    {
        $repo = new StockRepository;
        $user = $this->makeMember();

        $stock = $repo->create([
            'user_id' => $user->id, 'name' => 'B', 'quantity' => 1, 'unit' => 'pcs',
            'min_stock' => 5, 'status' => StockStatus::LowStock,
        ]);
        $repo->create([
            'user_id' => $user->id, 'name' => 'A', 'quantity' => 1, 'unit' => 'pcs',
            'min_stock' => 5, 'status' => StockStatus::LowStock,
        ]);

        $this->assertSame(['A', 'B'], $repo->allForUser($user)->pluck('name')->all());

        $repo->update($stock, ['name' => 'C']);
        $this->assertDatabaseHas('stocks', ['id' => $stock->id, 'name' => 'C']);

        $repo->delete($stock);
        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }
}
