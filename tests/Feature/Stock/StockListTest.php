<?php

namespace Tests\Feature\Stock;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Livewire\StockList;
use App\Features\Stock\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class StockListTest extends TestCase
{
    use RefreshDatabase;

    public function test_menampilkan_hanya_stok_milik_sendiri(): void
    {
        $user = $this->makeMember();
        Stock::factory()->for($user)->create(['name' => 'Barang Saya']);
        Stock::factory()->create(['name' => 'Barang Orang Lain']);

        Livewire::actingAs($user)->test(StockList::class)
            ->assertSee('Barang Saya')
            ->assertDontSee('Barang Orang Lain');
    }

    public function test_tampilan_kosong(): void
    {
        Livewire::actingAs($this->makeMember())->test(StockList::class)->assertSee('Belum ada stok.');
    }

    public function test_cari_dan_filter_status(): void
    {
        $user = $this->makeMember();
        Stock::factory()->for($user)->create(['name' => 'Kertas Folio', 'sku' => 'KRT-9']);
        Stock::factory()->for($user)->lowStock()->create(['name' => 'Pulpen Biru', 'sku' => 'PLP-9']);

        Livewire::actingAs($user)->test(StockList::class)
            ->set('search', 'Kertas')
            ->assertSee('Kertas Folio')->assertDontSee('Pulpen Biru')
            ->set('search', 'PLP-9')
            ->assertSee('Pulpen Biru')->assertDontSee('Kertas Folio')
            ->set('search', '')
            ->set('status', 'low_stock')
            ->assertSee('Pulpen Biru')->assertDontSee('Kertas Folio');
    }

    public function test_tambah_stok(): void
    {
        $user = $this->makeMember();

        Livewire::actingAs($user)->test(StockList::class)
            ->call('openCreate')
            ->assertSet('showForm', true)
            ->set('name', 'Tinta')
            ->set('sku', 'TNT-1')
            ->set('description', 'Tinta **hitam**')
            ->set('quantity', 2)
            ->set('unit', 'botol')
            ->set('price', '85000')
            ->set('min_stock', 5)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false)
            ->assertSee('Stok berhasil ditambahkan.');

        $this->assertDatabaseHas('stocks', [
            'user_id' => $user->id, 'name' => 'Tinta', 'sku' => 'TNT-1',
            'quantity' => 2, 'status' => StockStatus::LowStock->value,
        ]);
    }

    public function test_validasi_form(): void
    {
        Livewire::actingAs($this->makeMember())->test(StockList::class)
            ->call('openCreate')
            ->set('name', '')
            ->set('quantity', -1)
            ->set('unit', '')
            ->set('min_stock', '')
            ->call('save')
            ->assertHasErrors(['name' => 'required', 'quantity' => 'min', 'unit' => 'required', 'min_stock' => 'required']);
    }

    public function test_edit_stok(): void
    {
        $user = $this->makeMember();
        $stock = Stock::factory()->for($user)->create(['name' => 'Lama', 'quantity' => 50]);

        Livewire::actingAs($user)->test(StockList::class)
            ->call('openEdit', $stock->id)
            ->assertSet('editingId', $stock->id)
            ->assertSet('name', 'Lama')
            ->set('name', 'Baru')
            ->set('quantity', 0)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Stok berhasil diperbarui.');

        $this->assertDatabaseHas('stocks', [
            'id' => $stock->id, 'name' => 'Baru', 'status' => StockStatus::OutOfStock->value,
        ]);
    }

    public function test_close_form(): void
    {
        Livewire::actingAs($this->makeMember())->test(StockList::class)
            ->call('openCreate')
            ->set('name', 'x')
            ->call('closeForm')
            ->assertSet('showForm', false)
            ->assertSet('name', '');
    }

    public function test_tidak_bisa_mengubah_atau_menghapus_stok_orang_lain(): void
    {
        $user = $this->makeMember();
        $foreign = Stock::factory()->create(['quantity' => 10]);

        Livewire::actingAs($user)->test(StockList::class)->call('openEdit', $foreign->id)->assertForbidden();
        Livewire::actingAs($user)->test(StockList::class)->call('delete', $foreign->id)->assertForbidden();
        Livewire::actingAs($user)->test(StockList::class)->call('adjust', $foreign->id, 1)->assertForbidden();

        $this->assertDatabaseHas('stocks', ['id' => $foreign->id, 'quantity' => 10]);
    }

    public function test_admin_tidak_boleh_membuat_stok(): void
    {
        $admin = $this->makeAdmin();

        Livewire::actingAs($admin)->test(StockList::class)->call('openCreate')->assertForbidden();
        Livewire::actingAs($admin)->test(StockList::class)->call('openAi')->assertForbidden();
    }

    public function test_hapus_stok(): void
    {
        $user = $this->makeMember();
        $stock = Stock::factory()->for($user)->create();

        Livewire::actingAs($user)->test(StockList::class)
            ->call('delete', $stock->id)
            ->assertSee('Stok berhasil dihapus.');

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_adjust_quantity_menyesuaikan_status(): void
    {
        $user = $this->makeMember();
        $stock = Stock::factory()->for($user)->create(['quantity' => 6, 'min_stock' => 5]);

        $component = Livewire::actingAs($user)->test(StockList::class);

        $component->call('adjust', $stock->id, -1);
        $this->assertSame(StockStatus::LowStock, $stock->fresh()->status);

        $component->call('adjust', $stock->id, -50);
        $this->assertSame(0, $stock->fresh()->quantity);
        $this->assertSame(StockStatus::OutOfStock, $stock->fresh()->status);

        $component->call('adjust', $stock->id, 20);
        $this->assertSame(StockStatus::Available, $stock->fresh()->status);
    }

    public function test_export_excel(): void
    {
        $user = $this->makeMember();
        Stock::factory()->for($user)->create();

        Livewire::actingAs($user)->test(StockList::class)
            ->call('export')
            ->assertFileDownloaded();
    }

    public function test_import_excel(): void
    {
        $user = $this->makeMember();
        $file = UploadedFile::fake()->createWithContent('stok.xlsx', StockExcelServiceTest::makeXlsx([
            ['Map Plastik', 'MAP-1', 10, 'pcs', 2500, 5, '', ''],
            ['Lakban', 'LKB-1', 2, 'roll', 7000, 5, '', ''],
        ]));

        Livewire::actingAs($user)->test(StockList::class)
            ->set('importFile', $file)
            ->call('import')
            ->assertHasNoErrors()
            ->assertSee('2 stok berhasil diimpor.');

        $this->assertDatabaseHas('stocks', ['user_id' => $user->id, 'name' => 'Map Plastik']);
        $this->assertDatabaseHas('stocks', ['user_id' => $user->id, 'name' => 'Lakban']);
    }

    public function test_import_tanpa_file_dan_file_rusak(): void
    {
        $component = Livewire::actingAs($this->makeMember())->test(StockList::class);

        $component->call('import')->assertHasErrors(['importFile' => 'required']);

        $component
            ->set('importFile', UploadedFile::fake()->createWithContent('rusak.xlsx', 'bukan excel'))
            ->call('import')
            ->assertHasErrors(['importFile']);

        $this->assertSame(0, Stock::count());
    }

    public function test_generate_ai_preview_pilih_dan_simpan(): void
    {
        $user = $this->makeMember();

        $stub = $this->createStub(AiClientInterface::class);
        $stub->method('generateStockItems')->willReturn([
            new GeneratedStockItem('Stapler', 'STP-1', 'Stapler kecil', 10, 'pcs', 25000.0, 5),
            new GeneratedStockItem('Isi Staples', null, null, 2, 'box', null, 5),
        ]);
        $this->app->instance(AiClientInterface::class, $stub);

        Livewire::actingAs($user)->test(StockList::class)
            ->call('openAi')
            ->assertSet('showAi', true)
            ->set('aiPrompt', 'perlengkapan ATK')
            ->call('generateAi')
            ->assertHasNoErrors()
            ->assertSet('aiSelected', [0, 1])
            ->assertSee('Stapler')
            ->set('aiSelected', [0])
            ->call('saveAiItems')
            ->assertHasNoErrors()
            ->assertSet('showAi', false)
            ->assertSee('1 stok dari AI berhasil disimpan.');

        $this->assertDatabaseHas('stocks', ['user_id' => $user->id, 'name' => 'Stapler', 'sku' => 'STP-1']);
        $this->assertDatabaseMissing('stocks', ['name' => 'Isi Staples']);
    }

    public function test_generate_ai_validasi_dan_tanpa_pilihan(): void
    {
        $component = Livewire::actingAs($this->makeMember())->test(StockList::class)->call('openAi');

        $component->set('aiPrompt', '')->call('generateAi')->assertHasErrors(['aiPrompt' => 'required']);

        $component->set('aiSelected', [])->call('saveAiItems')->assertHasErrors(['aiSelected' => 'required']);
        $component->call('closeAi')->assertSet('showAi', false);
    }

    public function test_generate_ai_gagal_menampilkan_error(): void
    {
        $stub = $this->createStub(AiClientInterface::class);
        $stub->method('generateStockItems')->willThrowException(new RuntimeException('AI sedang bermasalah'));
        $this->app->instance(AiClientInterface::class, $stub);

        // DIPERBAIKI: modal AI harus dibuka dulu supaya pesan error ikut ter-render.
        Livewire::actingAs($this->makeMember())->test(StockList::class)
            ->call('openAi')
            ->set('aiPrompt', 'apa saja')
            ->call('generateAi')
            ->assertHasErrors('aiPrompt')
            ->assertSee('AI sedang bermasalah');
    }

    public function test_halaman_dapat_diakses_member(): void
    {
        $this->actingAs($this->makeMember())
            ->get('/stocks')
            ->assertOk()
            ->assertSeeLivewire(StockList::class);
    }
}