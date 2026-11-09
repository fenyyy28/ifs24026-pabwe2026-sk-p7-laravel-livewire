<?php

namespace App\Features\Stock\Contracts;

use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface StockRepositoryInterface
{
    public function paginateForUser(User $user, ?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator;

    /** @return Collection<int, Stock> */
    public function allForUser(User $user): Collection;

    public function create(array $data): Stock;

    public function update(Stock $stock, array $data): Stock;

    public function delete(Stock $stock): void;
}
