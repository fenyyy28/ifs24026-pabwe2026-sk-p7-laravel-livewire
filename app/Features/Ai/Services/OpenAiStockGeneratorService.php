<?php

namespace App\Features\Ai\Services;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiStockGeneratorService implements AiClientInterface
{
    public function generateStockItems(string $prompt, int $count = 5): array
    {
        $apiKey = config('openai.api_key');

        if (blank($apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY belum dikonfigurasi di file .env.');
        }

        $response = Http::withToken($apiKey)
            ->timeout((int) config('openai.timeout'))
            ->post(rtrim((string) config('openai.base_url'), '/').'/chat/completions', [
                'model' => config('openai.model'),
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt($count)],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Permintaan ke layanan AI gagal (HTTP '.$response->status().').');
        }

        $decoded = json_decode((string) $response->json('choices.0.message.content', ''), true);
        $items = is_array($decoded) && is_array($decoded['items'] ?? null) ? $decoded['items'] : [];

        $items = array_slice(array_filter($items, 'is_array'), 0, $count);

        return array_values(array_map(GeneratedStockItem::fromArray(...), $items));
    }

    private function systemPrompt(int $count): string
    {
        return 'Kamu adalah asisten manajemen inventaris. Berdasarkan permintaan pengguna, buat maksimal '.$count
            .' item stok barang. Balas HANYA dengan JSON: {"items":[{"name":string,"sku":string,'
            .'"description":string (boleh Markdown),"quantity":integer,"unit":string,'
            .'"price":number,"min_stock":integer}]}. Gunakan bahasa Indonesia.';
    }
}
