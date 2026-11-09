<?php

namespace App\Features\Ai\Contracts;

use App\Features\Ai\DTO\GeneratedStockItem;

interface AiClientInterface
{
    /**
     * Minta AI membuat daftar item inventaris dari deskripsi bebas.
     *
     * @return list<GeneratedStockItem>
     *
     * @throws \RuntimeException jika layanan AI tidak dapat dipakai
     */
    public function generateStockItems(string $prompt, int $count = 5): array;
}
