<?php

namespace Tests\Feature\Stock;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): StockService
    {
        return app(StockService::class);
    }

    public function test_create_menghitung_status_dan_normalisasi(): void
    {
        $user = $this->makeMember();

        $stock = $this->service()->create($user, [
            'name' => '  Kertas A4  ', 'sku' => '', 'description' => '',
            'quantity' => '3', 'unit' => '', 'price' => '', 'min_stock' => '5',
        ]);

        $this->assertSame('Kertas A4', $stock->name);
        $this->assertNull($stock->sku);
        $this->assertNull($stock->description);
        $this->assertNull($stock->price);
        $this->assertSame('pcs', $stock->unit);
        $this->assertSame(StockStatus::LowStock, $stock->status);
        $this->assertSame($user->id, $stock->user_id);
    }

    public function test_create_dengan_semua_field(): void
    {
        $stock = $this->service()->create($this->makeMember(), [
            'name' => 'X', 'sku' => ' S-1 ', 'description' => '**md**',
            'quantity' => 20, 'unit' => ' box ', 'price' => '2500.50', 'min_stock' => 5,
        ]);

        $this->assertSame('S-1', $stock->sku);
        $this->assertSame('**md**', $stock->description);
        $this->assertSame('box', $stock->unit);
        $this->assertSame('2500.50', $stock->price);
        $this->assertSame(StockStatus::Available, $stock->status);
    }

    public function test_update_menyinkronkan_status(): void
    {
        $stock = Stock::factory()->create();

        $this->service()->update($stock, ['name' => 'Baru', 'quantity' => 0, 'min_stock' => 5]);

        $this->assertSame('Baru', $stock->fresh()->name);
        $this->assertSame(StockStatus::OutOfStock, $stock->fresh()->status);
    }

    public function test_adjust_quantity(): void
    {
        $stock = Stock::factory()->create(['quantity' => 6, 'min_stock' => 5]);

        $this->service()->adjustQuantity($stock, -1);
        $this->assertSame(StockStatus::LowStock, $stock->fresh()->status);

        $this->service()->adjustQuantity($stock, -100);
        $this->assertSame(0, $stock->fresh()->quantity);
        $this->assertSame(StockStatus::OutOfStock, $stock->fresh()->status);

        $this->service()->adjustQuantity($stock, 10);
        $this->assertSame(StockStatus::Available, $stock->fresh()->status);
    }

    public function test_cover_set_replace_remove_dan_delete(): void
    {
        Storage::fake('public');
        $stock = Stock::factory()->create();

        $this->service()->setCover($stock, UploadedFile::fake()->image('a.jpg'));
        $first = $stock->fresh()->cover_image_path;
        Storage::disk('public')->assertExists($first);

        $this->service()->setCover($stock->fresh(), UploadedFile::fake()->image('b.jpg'));
        $second = $stock->fresh()->cover_image_path;
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->service()->removeCover($stock->fresh());
        Storage::disk('public')->assertMissing($second);
        $this->assertNull($stock->fresh()->cover_image_path);

        $this->service()->setCover($stock->fresh(), UploadedFile::fake()->image('c.jpg'));
        $third = $stock->fresh()->cover_image_path;
        $this->service()->delete($stock->fresh());
        Storage::disk('public')->assertMissing($third);
        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_delete_tanpa_cover(): void
    {
        $stock = Stock::factory()->create();

        $this->service()->delete($stock);

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_paginate(): void
    {
        $user = $this->makeMember();
        Stock::factory()->count(3)->for($user)->create();

        $this->assertSame(3, $this->service()->paginate($user)->total());
    }
}
