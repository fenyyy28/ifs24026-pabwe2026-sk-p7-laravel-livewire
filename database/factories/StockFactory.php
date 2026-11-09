<?php

namespace Database\Factories;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stock>
 */
class StockFactory extends Factory
{
    protected $model = Stock::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####-??')),
            'description' => fake()->sentence(),
            'quantity' => 50,
            'unit' => 'pcs',
            'price' => fake()->randomFloat(2, 1000, 500000),
            'min_stock' => 5,
            'status' => StockStatus::Available,
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn () => ['quantity' => 3, 'min_stock' => 5, 'status' => StockStatus::LowStock]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['quantity' => 0, 'min_stock' => 5, 'status' => StockStatus::OutOfStock]);
    }
}
