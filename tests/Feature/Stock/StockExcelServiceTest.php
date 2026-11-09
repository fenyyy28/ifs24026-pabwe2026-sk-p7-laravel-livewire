<?php

namespace Tests\Feature\Stock;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockExcelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StockExcelServiceTest extends TestCase
{
    use RefreshDatabase;

    public static function makeXlsx(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray(
            array_merge([StockExcelService::HEADERS], $rows),
            null,
            'A1',
        );

        $path = tempnam(sys_get_temp_dir(), 'tst');
        (new Xlsx($spreadsheet))->save($path);
        $content = (string) file_get_contents($path);
        unlink($path);

        return $content;
    }

    public function test_export_menghasilkan_xlsx_dengan_header_dan_data(): void
    {
        $user = $this->makeMember();
        Stock::factory()->for($user)->create(['name' => 'Kertas', 'sku' => 'KRT', 'quantity' => 7, 'price' => 1000]);

        $content = app(StockExcelService::class)->export($user->stocks()->get());

        $this->assertStringStartsWith('PK', $content);

        $path = tempnam(sys_get_temp_dir(), 'chk');
        file_put_contents($path, $content);
        $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, false);
        unlink($path);

        $this->assertSame(StockExcelService::HEADERS, $rows[0]);
        $this->assertSame('Kertas', $rows[1][0]);
        $this->assertSame('KRT', $rows[1][1]);
        $this->assertEquals(7, $rows[1][2]);
        $this->assertSame('available', $rows[1][6]);
    }

    public function test_import_membuat_stok_dan_melewati_baris_tanpa_nama(): void
    {
        $user = $this->makeMember();
        $path = tempnam(sys_get_temp_dir(), 'imp');
        file_put_contents($path, self::makeXlsx([
            ['Pulpen', 'PLP', 3, 'pcs', 2500, 5, 'available', 'Pulpen **gel**'],
            ['', 'KOSONG', 1, 'pcs', 100, 5, '', ''],
            ['Kertas', null, 50, null, null, 10, null, null],
            ['Spidol', 'SPD', 'abc', 'pcs', 'x', 5, '', ''],
        ]));

        $count = app(StockExcelService::class)->import($user, $path);
        unlink($path);

        $this->assertSame(3, $count);
        $this->assertDatabaseMissing('stocks', ['sku' => 'KOSONG']);

        $pulpen = Stock::where('name', 'Pulpen')->first();
        $this->assertSame(StockStatus::LowStock, $pulpen->status); // status dihitung ulang
        $this->assertSame('Pulpen **gel**', $pulpen->description);

        $kertas = Stock::where('name', 'Kertas')->first();
        $this->assertSame('pcs', $kertas->unit);
        $this->assertSame(StockStatus::Available, $kertas->status);

        $spidol = Stock::where('name', 'Spidol')->first();
        $this->assertSame(0, $spidol->quantity);
        $this->assertNull($spidol->price);
        $this->assertSame(StockStatus::OutOfStock, $spidol->status);
    }
}
