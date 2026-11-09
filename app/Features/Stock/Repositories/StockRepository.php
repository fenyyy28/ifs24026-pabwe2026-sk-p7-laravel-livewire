<?php

namespace App\Features\Stock\Repositories;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class StockRepository implements StockRepositoryInterface
{
    public function paginateForUser(User $user, ?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return Stock::query()
            ->where('user_id', $user->id)
            ->when(filled($search), function (Builder $query) use ($search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when(filled($status), fn (Builder $query) => $query->where('status', $status))
            ->latest('id')
            ->paginate($perPage);
    }

    public function allForUser(User $user): Collection
    {
        return Stock::query()->where('user_id', $user->id)->orderBy('name')->get();
    }

    public function create(array $data): Stock
    {
        return Stock::create($data);
    }

    public function update(Stock $stock, array $data): Stock
    {
        $stock->update($data);

        return $stock;
    }

    public function delete(Stock $stock): void
    {
        $stock->delete();
    }
}
