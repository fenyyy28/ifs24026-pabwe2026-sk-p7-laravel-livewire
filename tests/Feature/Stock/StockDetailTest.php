<?php

namespace Tests\Feature\Stock;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Livewire\StockDetail;
use App\Features\Stock\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StockDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_menampilkan_detail_dan_markdown(): void
    {
        $user = $this->makeMember();
        $stock = Stock::factory()->for($user)->create(['name' => 'Kertas', 'description' => 'Ini **tebal**']);

        $this->actingAs($user)->get("/stocks/{$stock->id}")
            ->assertOk()
            ->assertSeeLivewire(StockDetail::class)
            ->assertSee('Kertas')
            ->assertSeeHtml('<strong>tebal</strong>');
    }

    public function test_html_mentah_pada_markdown_dibuang(): void
    {
        $user = $this->makeMember();
        // DIPERBAIKI: tag <script> dan teks "halo" harus dipisah baris kosong,
        // kalau satu baris, seluruhnya dianggap satu blok HTML lalu dibuang.
        $stock = Stock::factory()->for($user)->create([
            'description' => "<script>alert(1)</script>\n\nhalo",
        ]);

        Livewire::actingAs($user)->test(StockDetail::class, ['stock' => $stock])
            ->assertDontSeeHtml('<script>alert(1)</script>')
            ->assertSee('halo');
    }

    public function test_tanpa_deskripsi(): void
    {
        $user = $this->makeMember();
        $stock = Stock::factory()->for($user)->create(['description' => null]);

        Livewire::actingAs($user)->test(StockDetail::class, ['stock' => $stock])
            ->assertSee('Belum ada deskripsi.');
    }

    public function test_edit_dan_batal(): void
    {
        $user = $this->makeMember();
        $stock = Stock::factory()->for($user)->create(['name' => 'Lama']);

        Livewire::actingAs($user)->test(StockDetail::class, ['stock' => $stock])
            ->call('startEdit')
            ->assertSet('editing', true)
            ->call('cancelEdit')
            ->assertSet('editing', false)
            ->call('startEdit')
            ->set('name', 'Baru')
            ->set('description', '# Judul')
            ->set('quantity', 3)
            ->set('min_stock', 5)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('editing', false)
            ->assertSee('Stok berhasil diperbarui.');

        $fresh = $stock->fresh();
        $this->assertSame('Baru', $fresh->name);
        $this->assertSame('# Judul', $fresh->description);
        $this->assertSame(StockStatus::LowStock, $fresh->status);
    }

    public function test_validasi_edit(): void
    {
        $user = $this->makeMember();
        $stock = Stock::factory()->for($user)->create();

        Livewire::actingAs($user)->test(StockDetail::class, ['stock' => $stock])
            ->call('startEdit')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['name' => 'required']);
    }

    public function test_adjust_quantity(): void
    {
        $user = $this->makeMember();
        $stock = Stock::factory()->for($user)->create(['quantity' => 6, 'min_stock' => 5]);

        Livewire::actingAs($user)->test(StockDetail::class, ['stock' => $stock])
            ->call('adjust', -1)
            ->call('adjust', -10)
            ->call('adjust', 1);

        $this->assertSame(1, $stock->fresh()->quantity);
        $this->assertSame(StockStatus::LowStock, $stock->fresh()->status);
    }

    public function test_upload_dan_hapus_cover(): void
    {
        Storage::fake('public');
        $user = $this->makeMember();
        $stock = Stock::factory()->for($user)->create();

        $component = Livewire::actingAs($user)->test(StockDetail::class, ['stock' => $stock])
            ->set('cover', UploadedFile::fake()->image('cover.jpg'))
            ->call('uploadCover')
            ->assertHasNoErrors()
            ->assertSee('Cover berhasil diperbarui.');

        $path = $stock->fresh()->cover_image_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $component->call('removeCover')->assertSee('Cover dihapus.');
        $this->assertNull($stock->fresh()->cover_image_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_cover_harus_gambar(): void
    {
        Storage::fake('public');
        $user = $this->makeMember();
        $stock = Stock::factory()->for($user)->create();

        Livewire::actingAs($user)->test(StockDetail::class, ['stock' => $stock])
            ->set('cover', UploadedFile::fake()->create('dok.pdf', 10, 'application/pdf'))
            ->call('uploadCover')
            ->assertHasErrors(['cover']);
    }

    public function test_hapus_stok(): void
    {
        $user = $this->makeMember();
        $stock = Stock::factory()->for($user)->create();

        Livewire::actingAs($user)->test(StockDetail::class, ['stock' => $stock])
            ->call('delete')
            ->assertRedirect('/stocks');

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }
}