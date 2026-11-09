<?php

namespace Tests\Feature\Stock;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StockModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_relasi_dan_cast(): void
    {
        $user = $this->makeMember();
        $stock = Stock::factory()->for($user)->create(['price' => 1500]);

        $this->assertTrue($stock->user->is($user));
        $this->assertTrue($user->stocks->first()->is($stock));
        $this->assertSame(StockStatus::Available, $stock->status);
        $this->assertSame('1500.00', $stock->price);
    }

    public function test_factory_state(): void
    {
        $this->assertSame(StockStatus::LowStock, Stock::factory()->lowStock()->create()->status);
        $this->assertSame(StockStatus::OutOfStock, Stock::factory()->outOfStock()->create()->status);
    }

    public function test_cover_image_url(): void
    {
        Storage::fake('public');

        $this->assertNull(Stock::factory()->create()->coverImageUrl());

        $stock = Stock::factory()->create(['cover_image_path' => 'stock-covers/a.jpg']);
        $this->assertStringContainsString('stock-covers/a.jpg', $stock->coverImageUrl());
    }

    public function test_hapus_user_menghapus_stok(): void
    {
        $stock = Stock::factory()->create();
        $stock->user->delete();

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_user_helpers(): void
    {
        Storage::fake('public');

        $member = $this->makeMember();
        $this->assertFalse($member->isAdmin());
        $this->assertNull($member->profilePhotoUrl());

        $member->update(['profile_photo_path' => 'profile-photos/me.jpg']);
        $this->assertStringContainsString('profile-photos/me.jpg', $member->profilePhotoUrl());

        $this->assertTrue($this->makeAdmin()->isAdmin());
        $this->assertInstanceOf(User::class, $member);
    }
}
