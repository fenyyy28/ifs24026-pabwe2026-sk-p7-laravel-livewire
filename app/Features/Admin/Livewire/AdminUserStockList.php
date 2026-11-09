<?php

namespace App\Features\Admin\Livewire;

use App\Features\Admin\Services\AdminUserService;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Halaman read-only: admin hanya melihat stok milik user, tanpa aksi ubah/hapus.
 */
#[Layout('components.layouts.app')]
#[Title('Stok User')]
class AdminUserStockList extends Component
{
    use WithPagination;

    public User $user;

    public string $search = '';

    public string $status = '';

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.admin.admin-user-stock-list', [
            'stocks' => app(AdminUserService::class)->paginateStocksOf($this->user, $this->search, $this->status),
        ]);
    }
}
