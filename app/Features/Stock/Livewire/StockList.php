<?php

namespace App\Features\Stock\Livewire;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockExcelService;
use App\Features\Stock\Services\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use RuntimeException;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('Daftar Stok')]
class StockList extends Component
{
    use WithFileUploads;
    use WithPagination;

    // Filter
    public string $search = '';

    public string $status = '';

    // Form create / edit (modal)
    public bool $showForm = false;

    public ?int $editingId = null;

    public $name = '';

    public $sku = '';

    public $description = '';

    public $quantity = 0;

    public $unit = 'pcs';

    public $price = '';

    public $min_stock = 5;

    // Import Excel
    public $importFile = null;

    // Generate AI
    public bool $showAi = false;

    public string $aiPrompt = '';

    public $aiCount = 5;

    public array $aiItems = [];

    public array $aiSelected = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    /* ---------------------------------------------------------------- CRUD */

    public function openCreate(): void
    {
        Gate::authorize('create', Stock::class);

        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $stock = $this->ownedStock($id, 'update');

        $this->editingId = $stock->id;
        $this->name = $stock->name;
        $this->sku = $stock->sku;
        $this->description = $stock->description;
        $this->quantity = $stock->quantity;
        $this->unit = $stock->unit;
        $this->price = $stock->price;
        $this->min_stock = $stock->min_stock;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->resetForm();
    }

    public function save(StockService $service): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:5000',
            'quantity' => 'required|integer|min:0',
            'unit' => 'required|string|max:20',
            'price' => 'nullable|numeric|min:0',
            'min_stock' => 'required|integer|min:0',
        ]);

        if ($this->editingId === null) {
            Gate::authorize('create', Stock::class);
            $service->create(Auth::user(), $data);
            $message = 'Stok berhasil ditambahkan.';
        } else {
            $service->update($this->ownedStock($this->editingId, 'update'), $data);
            $message = 'Stok berhasil diperbarui.';
        }

        $this->resetForm();
        session()->flash('status', $message);
    }

    public function delete(int $id, StockService $service): void
    {
        $service->delete($this->ownedStock($id, 'delete'));

        session()->flash('status', 'Stok berhasil dihapus.');
    }

    public function adjust(int $id, int $delta, StockService $service): void
    {
        $service->adjustQuantity($this->ownedStock($id, 'update'), $delta);
    }

    /* --------------------------------------------------------------- Excel */

    public function export(StockExcelService $excel)
    {
        $content = $excel->export(app(StockRepositoryInterface::class)->allForUser(Auth::user()));

        return response()->streamDownload(
            function () use ($content) {
                echo $content;
            },
            'stok-'.now()->format('Ymd-His').'.xlsx',
        );
    }

    public function import(StockExcelService $excel): void
    {
        $this->validate(['importFile' => 'required|file|extensions:xlsx,xls|max:2048']);

        try {
            $count = $excel->import(Auth::user(), $this->importFile->getRealPath());
        } catch (Throwable) {
            $this->addError('importFile', 'File Excel tidak dapat dibaca.');

            return;
        }

        $this->reset('importFile');
        session()->flash('status', "{$count} stok berhasil diimpor.");
    }

    /* ------------------------------------------------------------------ AI */

    public function openAi(): void
    {
        Gate::authorize('create', Stock::class);

        $this->resetAi();
        $this->showAi = true;
    }

    public function closeAi(): void
    {
        $this->resetAi();
    }

    public function generateAi(): void
    {
        $this->validate([
            'aiPrompt' => 'required|string|min:3|max:500',
            'aiCount' => 'required|integer|min:1|max:10',
        ]);

        try {
            $items = app(AiClientInterface::class)->generateStockItems($this->aiPrompt, (int) $this->aiCount);
        } catch (RuntimeException $e) {
            $this->addError('aiPrompt', $e->getMessage());

            return;
        }

        $this->aiItems = array_map(fn (GeneratedStockItem $item) => $item->toArray(), $items);
        $this->aiSelected = array_keys($this->aiItems);
    }

    public function saveAiItems(StockService $service): void
    {
        $this->validate(['aiSelected' => 'required|array|min:1']);
        Gate::authorize('create', Stock::class);

        $chosen = array_intersect_key($this->aiItems, array_flip($this->aiSelected));

        foreach ($chosen as $item) {
            $service->create(Auth::user(), $item);
        }

        $this->resetAi();
        session()->flash('status', count($chosen).' stok dari AI berhasil disimpan.');
    }

    /* ------------------------------------------------------------- Helpers */

    private function ownedStock(int $id, string $ability): Stock
    {
        $stock = Stock::findOrFail($id);
        Gate::authorize($ability, $stock);

        return $stock;
    }

    private function resetForm(): void
    {
        $this->reset('showForm', 'editingId', 'name', 'sku', 'description', 'quantity', 'unit', 'price', 'min_stock');
        $this->resetValidation();
    }

    private function resetAi(): void
    {
        $this->reset('showAi', 'aiPrompt', 'aiCount', 'aiItems', 'aiSelected');
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.stock.stock-list', [
            'stocks' => app(StockService::class)->paginate(Auth::user(), $this->search, $this->status),
            'statuses' => StockStatus::cases(),
        ]);
    }
}
