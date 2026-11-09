<?php

namespace Tests\Feature\Ai;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Ai\Services\OpenAiStockGeneratorService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenAiStockGeneratorServiceTest extends TestCase
{
    private function fakeReply(string $content, int $status = 200): void
    {
        Http::fake([
            '*/chat/completions' => Http::response(
                ['choices' => [['message' => ['content' => $content]]]],
                $status,
            ),
        ]);
    }

    public function test_container_mengikat_interface_ke_service(): void
    {
        $this->assertInstanceOf(OpenAiStockGeneratorService::class, app(AiClientInterface::class));
    }

    public function test_tanpa_api_key_melempar_exception(): void
    {
        config(['openai.api_key' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OPENAI_API_KEY');

        (new OpenAiStockGeneratorService)->generateStockItems('ATK');
    }

    public function test_respon_gagal_melempar_exception(): void
    {
        config(['openai.api_key' => 'sk-test']);
        $this->fakeReply('{}', 500);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTP 500');

        (new OpenAiStockGeneratorService)->generateStockItems('ATK');
    }

    public function test_sukses_mengembalikan_dto_dan_membatasi_jumlah(): void
    {
        config(['openai.api_key' => 'sk-test', 'openai.model' => 'model-x']);
        $this->fakeReply(json_encode(['items' => [
            ['name' => 'Stapler', 'sku' => 'STP', 'description' => 'Kecil', 'quantity' => 10, 'unit' => 'pcs', 'price' => 25000, 'min_stock' => 3],
            'bukan-array',
            ['name' => 'Isi Staples'],
            ['name' => 'Lem'],
        ]]));

        $items = (new OpenAiStockGeneratorService)->generateStockItems('ATK kantor', 2);

        $this->assertCount(2, $items);
        $this->assertContainsOnlyInstancesOf(GeneratedStockItem::class, $items);
        $this->assertSame('Stapler', $items[0]->name);
        $this->assertSame(25000.0, $items[0]->price);
        $this->assertSame('Isi Staples', $items[1]->name);

        Http::assertSent(function (Request $request) {
            return $request->hasHeader('Authorization', 'Bearer sk-test')
                && $request['model'] === 'model-x'
                && $request['messages'][1]['content'] === 'ATK kantor'
                && str_contains($request['messages'][0]['content'], '2');
        });
    }

    public function test_konten_bukan_json_menghasilkan_daftar_kosong(): void
    {
        config(['openai.api_key' => 'sk-test']);
        $this->fakeReply('ini bukan json');

        $this->assertSame([], (new OpenAiStockGeneratorService)->generateStockItems('ATK'));
    }

    public function test_dto_default_dan_to_array(): void
    {
        $dto = GeneratedStockItem::fromArray([]);

        $this->assertSame('Item tanpa nama', $dto->name);
        $this->assertNull($dto->sku);
        $this->assertNull($dto->description);
        $this->assertSame(0, $dto->quantity);
        $this->assertSame('pcs', $dto->unit);
        $this->assertNull($dto->price);
        $this->assertSame(5, $dto->minStock);

        $this->assertSame([
            'name' => 'Item tanpa nama', 'sku' => null, 'description' => null, 'quantity' => 0,
            'unit' => 'pcs', 'price' => null, 'min_stock' => 5,
        ], $dto->toArray());
    }
}
