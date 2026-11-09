<?php

namespace App\Features\Admin\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminUserService
{
    public function __construct(private readonly StockRepositoryInterface $stocks) {}

    /**
     * Daftar user beserta ringkasan jumlah stok & total quantity.
     */
    public function paginateUsers(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return User::query()
            ->withCount('stocks')
            ->withSum('stocks as stocks_quantity_sum', 'quantity')
            ->when(filled($search), function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Stok milik $user (read-only dari sisi admin).
     */
    public function paginateStocksOf(User $user, ?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->stocks->paginateForUser($user, $search, $status, $perPage);
    }
}
