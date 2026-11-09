<?php

namespace Tests\Unit\Stock;

use App\Features\Stock\Enums\StockStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StockStatusTest extends TestCase
{
    public static function quantities(): array
    {
        return [
            'habis' => [0, 5, StockStatus::OutOfStock],
            'menipis (= min)' => [5, 5, StockStatus::LowStock],
            'menipis (< min)' => [3, 5, StockStatus::LowStock],
            'tersedia' => [6, 5, StockStatus::Available],
        ];
    }

    #[DataProvider('quantities')]
    public function test_from_quantity(int $qty, int $min, StockStatus $expected): void
    {
        $this->assertSame($expected, StockStatus::fromQuantity($qty, $min));
    }

    public function test_label_dan_badge_class(): void
    {
        $this->assertSame('Tersedia', StockStatus::Available->label());
        $this->assertSame('Stok Menipis', StockStatus::LowStock->label());
        $this->assertSame('Habis', StockStatus::OutOfStock->label());

        $this->assertStringContainsString('green', StockStatus::Available->badgeClass());
        $this->assertStringContainsString('yellow', StockStatus::LowStock->badgeClass());
        $this->assertStringContainsString('red', StockStatus::OutOfStock->badgeClass());
    }
}
