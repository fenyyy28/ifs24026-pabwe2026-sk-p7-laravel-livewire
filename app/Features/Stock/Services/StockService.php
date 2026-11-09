<?php

namespace App\Features\Stock\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class StockService
{
    public function __construct(private readonly StockRepositoryInterface $repository) {}

    public function paginate(User $user, ?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginateForUser($user, $search, $status, $perPage);
    }

    public function create(User $user, array $data): Stock
    {
        return $this->repository->create(['user_id' => $user->id] + $this->payload($data));
    }

    public function update(Stock $stock, array $data): Stock
    {
        return $this->repository->update($stock, $this->payload($data));
    }

    public function delete(Stock $stock): void
    {
        $this->deleteCoverFile($stock);
        $this->repository->delete($stock);
    }

    /**
     * Tambah / kurangi quantity (tidak boleh di bawah 0) lalu sinkronkan status.
     */
    public function adjustQuantity(Stock $stock, int $delta): Stock
    {
        $quantity = max(0, $stock->quantity + $delta);

        return $this->repository->update($stock, [
            'quantity' => $quantity,
            'status' => StockStatus::fromQuantity($quantity, $stock->min_stock),
        ]);
    }

    public function setCover(Stock $stock, UploadedFile $file): Stock
    {
        $this->deleteCoverFile($stock);

        return $this->repository->update($stock, [
            'cover_image_path' => $file->store('stock-covers', 'public'),
        ]);
    }

    public function removeCover(Stock $stock): Stock
    {
        $this->deleteCoverFile($stock);

        return $this->repository->update($stock, ['cover_image_path' => null]);
    }

    private function deleteCoverFile(Stock $stock): void
    {
        if ($stock->cover_image_path !== null) {
            Storage::disk('public')->delete($stock->cover_image_path);
        }
    }

    /**
     * Normalisasi input form/Excel/AI menjadi atribut model, termasuk status otomatis.
     */
    private function payload(array $data): array
    {
        $quantity = max(0, (int) ($data['quantity'] ?? 0));
        $minStock = max(0, (int) ($data['min_stock'] ?? 5));

        return [
            'name' => trim((string) $data['name']),
            'sku' => filled($data['sku'] ?? null) ? trim((string) $data['sku']) : null,
            'description' => filled($data['description'] ?? null) ? (string) $data['description'] : null,
            'quantity' => $quantity,
            'unit' => filled($data['unit'] ?? null) ? trim((string) $data['unit']) : 'pcs',
            'price' => is_numeric($data['price'] ?? null) ? $data['price'] : null,
            'min_stock' => $minStock,
            'status' => StockStatus::fromQuantity($quantity, $minStock),
        ];
    }
}
