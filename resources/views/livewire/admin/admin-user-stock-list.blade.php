<div class="space-y-4">
    <div>
        <a href="{{ route('admin.users') }}" class="text-sm text-indigo-600 hover:underline">&larr; Kembali ke daftar user</a>
        <h1 class="mt-1 text-2xl font-bold">Stok milik {{ $user->name }} <span class="text-base font-normal text-gray-500">({{ $user->username }})</span></h1>
        <p class="text-sm text-gray-500">Mode baca saja (read-only).</p>
    </div>

    <div class="flex flex-wrap gap-2">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama / SKU..."
               class="w-64 rounded border px-3 py-2 text-sm">
        <select wire:model.live="status" class="rounded border px-3 py-2 text-sm">
            <option value="">Semua status</option>
            @foreach (\App\Features\Stock\Enums\StockStatus::cases() as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-x-auto rounded-lg bg-white shadow">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100 text-left">
                <tr>
                    <th class="px-4 py-2">Nama</th>
                    <th class="px-4 py-2">SKU</th>
                    <th class="px-4 py-2 text-right">Qty</th>
                    <th class="px-4 py-2">Satuan</th>
                    <th class="px-4 py-2 text-right">Harga</th>
                    <th class="px-4 py-2 text-right">Min</th>
                    <th class="px-4 py-2">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stocks as $stock)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $stock->name }}</td>
                        <td class="px-4 py-2">{{ $stock->sku }}</td>
                        <td class="px-4 py-2 text-right">{{ $stock->quantity }}</td>
                        <td class="px-4 py-2">{{ $stock->unit }}</td>
                        <td class="px-4 py-2 text-right">{{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '-' }}</td>
                        <td class="px-4 py-2 text-right">{{ $stock->min_stock }}</td>
                        <td class="px-4 py-2">
                            <span class="rounded px-2 py-0.5 text-xs font-medium {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">User ini belum punya stok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $stocks->links() }}
</div>
