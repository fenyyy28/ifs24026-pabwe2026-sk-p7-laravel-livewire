<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-2xl font-bold">Daftar Stok</h1>
        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="openCreate" id="stock-create" class="rounded bg-indigo-600 px-3 py-2 text-sm text-white hover:bg-indigo-700">+ Tambah Stok</button>
            <button type="button" wire:click="openAi" id="stock-ai" class="rounded bg-purple-600 px-3 py-2 text-sm text-white hover:bg-purple-700">✨ Generate AI</button>
            <button type="button" wire:click="export" id="stock-export" class="rounded border bg-white px-3 py-2 text-sm hover:bg-gray-50">Export Excel</button>
        </div>
    </div>

    @if (session('status'))
        <div class="rounded bg-green-100 px-4 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    {{-- Import Excel --}}
    <form wire:submit="import" class="flex flex-wrap items-center gap-2 rounded-lg bg-white p-3 shadow">
        <label for="stock-import-file" class="text-sm font-medium">Import Excel:</label>
        <input type="file" id="stock-import-file" wire:model="importFile" accept=".xlsx,.xls" class="text-sm">
        <button type="submit" id="stock-import" class="rounded border px-3 py-1 text-sm hover:bg-gray-50">Import</button>
        <span class="text-xs text-gray-500">Kolom: name, sku, qty, unit, price, min_stock, status, description</span>
        @error('importFile') <p class="w-full text-sm text-red-600">{{ $message }}</p> @enderror
    </form>

    {{-- Filter --}}
    <div class="flex flex-wrap gap-2">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama / SKU..."
               class="w-64 rounded border px-3 py-2 text-sm">
        <select wire:model.live="status" class="rounded border px-3 py-2 text-sm">
            <option value="">Semua status</option>
            @foreach ($statuses as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>

    {{-- Tabel --}}
    <div class="overflow-x-auto rounded-lg bg-white shadow">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100 text-left">
                <tr>
                    <th class="px-4 py-2">Nama</th>
                    <th class="px-4 py-2">SKU</th>
                    <th class="px-4 py-2 text-center">Qty</th>
                    <th class="px-4 py-2">Satuan</th>
                    <th class="px-4 py-2 text-right">Harga</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stocks as $stock)
                    <tr class="border-t" wire:key="stock-{{ $stock->id }}">
                        <td class="px-4 py-2">
                            <a href="{{ route('stocks.show', $stock) }}" class="font-medium text-indigo-600 hover:underline">{{ $stock->name }}</a>
                        </td>
                        <td class="px-4 py-2">{{ $stock->sku }}</td>
                        <td class="px-4 py-2">
                            <div class="flex items-center justify-center gap-2">
                                <button type="button" wire:click="adjust({{ $stock->id }}, -1)" class="h-7 w-7 rounded border hover:bg-gray-100" aria-label="Kurangi">−</button>
                                <span class="w-10 text-center font-semibold">{{ $stock->quantity }}</span>
                                <button type="button" wire:click="adjust({{ $stock->id }}, 1)" class="h-7 w-7 rounded border hover:bg-gray-100" aria-label="Tambah">+</button>
                            </div>
                        </td>
                        <td class="px-4 py-2">{{ $stock->unit }}</td>
                        <td class="px-4 py-2 text-right">{{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '-' }}</td>
                        <td class="px-4 py-2">
                            <span class="rounded px-2 py-0.5 text-xs font-medium {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <button type="button" wire:click="openEdit({{ $stock->id }})" class="text-indigo-600 hover:underline">Edit</button>
                            <button type="button" wire:click="delete({{ $stock->id }})" wire:confirm="Hapus stok ini?" class="ml-2 text-red-600 hover:underline">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">Belum ada stok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $stocks->links() }}

    {{-- Modal create / edit --}}
    @if ($showForm)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4">
            <div class="max-h-full w-full max-w-lg overflow-y-auto rounded-lg bg-white p-6 shadow-xl">
                <h2 class="mb-4 text-lg font-semibold">{{ $editingId ? 'Edit Stok' : 'Tambah Stok' }}</h2>

                <form wire:submit="save" class="space-y-3">
                    <div>
                        <label for="stock-name" class="mb-1 block text-sm font-medium">Nama</label>
                        <input type="text" id="stock-name" wire:model="name" class="w-full rounded border px-3 py-2">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="stock-sku" class="mb-1 block text-sm font-medium">SKU</label>
                        <input type="text" id="stock-sku" wire:model="sku" class="w-full rounded border px-3 py-2">
                        @error('sku') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="stock-description" class="mb-1 block text-sm font-medium">Deskripsi (Markdown)</label>
                        <textarea id="stock-description" wire:model="description" rows="3" class="w-full rounded border px-3 py-2"></textarea>
                        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label for="stock-quantity" class="mb-1 block text-sm font-medium">Quantity</label>
                            <input type="number" min="0" id="stock-quantity" wire:model="quantity" class="w-full rounded border px-3 py-2">
                            @error('quantity') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="stock-unit" class="mb-1 block text-sm font-medium">Satuan</label>
                            <input type="text" id="stock-unit" wire:model="unit" class="w-full rounded border px-3 py-2">
                            @error('unit') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="stock-min-stock" class="mb-1 block text-sm font-medium">Min. Stok</label>
                            <input type="number" min="0" id="stock-min-stock" wire:model="min_stock" class="w-full rounded border px-3 py-2">
                            @error('min_stock') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label for="stock-price" class="mb-1 block text-sm font-medium">Harga</label>
                        <input type="number" min="0" step="0.01" id="stock-price" wire:model="price" class="w-full rounded border px-3 py-2">
                        @error('price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <p class="text-xs text-gray-500">Status (Tersedia / Stok Menipis / Habis) dihitung otomatis dari quantity dan min. stok.</p>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeForm" class="rounded border px-4 py-2 text-sm">Batal</button>
                        <button type="submit" id="stock-save" class="rounded bg-indigo-600 px-4 py-2 text-sm text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal AI --}}
    @if ($showAi)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4">
            <div class="max-h-full w-full max-w-2xl overflow-y-auto rounded-lg bg-white p-6 shadow-xl">
                <h2 class="mb-4 text-lg font-semibold">✨ Generate Item Stok dengan AI</h2>

                <form wire:submit="generateAi" class="space-y-3">
                    <div>
                        <label for="ai-prompt" class="mb-1 block text-sm font-medium">Deskripsikan stok yang dibutuhkan</label>
                        <textarea id="ai-prompt" wire:model="aiPrompt" rows="2" placeholder="Contoh: perlengkapan ATK untuk kantor kecil"
                                  class="w-full rounded border px-3 py-2"></textarea>
                        @error('aiPrompt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end gap-3">
                        <div>
                            <label for="ai-count" class="mb-1 block text-sm font-medium">Jumlah item</label>
                            <input type="number" min="1" max="10" id="ai-count" wire:model="aiCount" class="w-24 rounded border px-3 py-2">
                            @error('aiCount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" id="ai-generate" class="rounded bg-purple-600 px-4 py-2 text-sm text-white" wire:loading.attr="disabled" wire:target="generateAi">
                            <span wire:loading.remove wire:target="generateAi">Generate</span>
                            <span wire:loading wire:target="generateAi">Memproses...</span>
                        </button>
                    </div>
                </form>

                @if (count($aiItems) > 0)
                    <div class="mt-4 space-y-2">
                        <h3 class="text-sm font-semibold">Preview — pilih item yang ingin disimpan</h3>
                        @foreach ($aiItems as $i => $item)
                            <label class="flex items-start gap-3 rounded border p-3 text-sm" wire:key="ai-{{ $i }}">
                                <input type="checkbox" wire:model="aiSelected" value="{{ $i }}" class="mt-1">
                                <div class="flex-1">
                                    <div class="font-medium">{{ $item['name'] }} <span class="text-xs text-gray-500">{{ $item['sku'] }}</span></div>
                                    <div class="text-gray-600">{{ $item['description'] }}</div>
                                    <div class="text-xs text-gray-500">
                                        Qty {{ $item['quantity'] }} {{ $item['unit'] }} · Min {{ $item['min_stock'] }}
                                        · {{ $item['price'] !== null ? 'Rp '.number_format($item['price'], 0, ',', '.') : 'tanpa harga' }}
                                    </div>
                                </div>
                            </label>
                        @endforeach
                        @error('aiSelected') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" wire:click="closeAi" class="rounded border px-4 py-2 text-sm">Tutup</button>
                    @if (count($aiItems) > 0)
                        <button type="button" wire:click="saveAiItems" id="ai-save" class="rounded bg-indigo-600 px-4 py-2 text-sm text-white">Simpan yang dipilih</button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
