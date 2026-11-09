<?php

namespace Database\Seeders;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Administrator',
            'username' => 'admin',
            'password' => 'admin',
            'role' => User::ROLE_ADMIN,
        ]);

        $member = User::create([
            'name' => 'Member Demo',
            'username' => 'member',
            'password' => 'member',
            'role' => User::ROLE_MEMBER,
        ]);

        $rows = [
            ['Kertas A4 80gsm', 'KRT-A4-80', 'Kertas fotokopi **80gsm** (1 rim = 500 lembar).', 120, 'rim', 55000, 20],
            ['Pulpen Hitam', 'PLP-BLK-01', 'Pulpen gel 0.5mm.', 4, 'pcs', 3500, 10],
            ['Spidol Whiteboard', 'SPD-WB-02', 'Tinta cepat kering.', 0, 'pcs', 9000, 5],
            ['Map Plastik', 'MAP-PL-03', 'Map folder transparan.', 60, 'pcs', 2500, 15],
            ['Tinta Printer 003', 'TNT-003', 'Tinta original.', 2, 'botol', 85000, 3],
        ];

        foreach ($rows as [$name, $sku, $desc, $qty, $unit, $price, $min]) {
            Stock::create([
                'user_id' => $member->id,
                'name' => $name,
                'sku' => $sku,
                'description' => $desc,
                'quantity' => $qty,
                'unit' => $unit,
                'price' => $price,
                'min_stock' => $min,
                'status' => StockStatus::fromQuantity($qty, $min),
            ]);
        }
    }
}
