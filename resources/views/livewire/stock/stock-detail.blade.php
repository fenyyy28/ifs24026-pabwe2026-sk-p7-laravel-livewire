@push('head')
    {{-- Toast UI Editor hanya dimuat di halaman detail stok --}}
    <link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/toastui-editor.min.css">
    <script src="https://uicdn.toast.com/editor/latest/toastui-editor-all.min.js" defer></script>
@endpush

<div class="mx-auto max-w-4xl space-y-4">
    <a href="{{ route('stocks.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; Kembali ke daftar stok</a>

    @if (session('status'))
        <div class="rounded bg-green-100 px-4 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        {{-- Cover --}}
        <section class="space-y-3 rounded-lg bg-white p-4 shadow">
            @if ($stock->coverImageUrl())
                <img src="{{ $stock->coverImageUrl() }}" alt="Cover {{ $stock->name }}" class="w-full rounded object-cover">
                <button type="button" wire:click="removeCover" class="text-sm text-red-600 hover:underline">Hapus cover</button>
            @else
                <div class="flex h-40 items-center justify-center rounded bg-gray-100 text-4xl">📦</div>
            @endif

            <form wire:submit="uploadCover" class="space-y-2">
                <input type="file" wire:model="cover" accept="image/*" class="w-full text-xs">
                @error('cover') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                <button type="submit" class="rounded border px-3 py-1 text-sm hover:bg-gray-50">Upload cover</button>
            </form>
        </section>

        {{-- Info --}}
        <section class="space-y-4 rounded-lg bg-white p-4 shadow md:col-span-2">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <h1 class="text-2xl font-bold">{{ $stock->name }}</h1>
                    <p class="text-sm text-gray-500">SKU: {{ $stock->sku ?? '-' }}</p>
                </div>
                <span class="rounded px-2 py-1 text-xs font-medium {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" wire:click="adjust(-1)" class="h-9 w-9 rounded border text-lg hover:bg-gray-100" aria-label="Kurangi">−</button>
                <div class="text-center">
                    <div class="text-3xl font-bold">{{ $stock->quantity }}</div>
                    <div class="text-xs text-gray-500">{{ $stock->unit }} · min {{ $stock->min_stock }}</div>
                </div>
                <button type="button" wire:click="adjust(1)" class="h-9 w-9 rounded border text-lg hover:bg-gray-100" aria-label="Tambah">+</button>
                <div class="ml-auto text-right text-sm">
                    Harga: <strong>{{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '-' }}</strong>
                </div>
            </div>

            @unless ($editing)
                <div>
                    <h2 class="mb-1 text-sm font-semibold text-gray-600">Deskripsi</h2>
                    <div class="prose max-w-none text-sm [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_h1]:text-xl [&_h1]:font-bold [&_h2]:text-lg [&_h2]:font-semibold [&_a]:text-indigo-600 [&_a]:underline">
                        @if (filled($stock->description))
                            {!! $descriptionHtml !!}
                        @else
                            <p class="text-gray-400">Belum ada deskripsi.</p>
                        @endif
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="button" wire:click="startEdit" id="stock-edit" class="rounded bg-indigo-600 px-4 py-2 text-sm text-white">Edit</button>
                    <button type="button" wire:click="delete" wire:confirm="Hapus stok ini?" class="rounded border border-red-300 px-4 py-2 text-sm text-red-600">Hapus</button>
                </div>
            @endunless
        </section>
    </div>

    {{-- Form edit --}}
    @if ($editing)
        <section class="rounded-lg bg-white p-4 shadow">
            <h2 class="mb-3 font-semibold">Edit Stok</h2>
            <form wire:submit="save" class="space-y-3">
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label for="detail-name" class="mb-1 block text-sm font-medium">Nama</label>
                        <input type="text" id="detail-name" wire:model="name" class="w-full rounded border px-3 py-2">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="detail-sku" class="mb-1 block text-sm font-medium">SKU</label>
                        <input type="text" id="detail-sku" wire:model="sku" class="w-full rounded border px-3 py-2">
                        @error('sku') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="grid gap-3 md:grid-cols-4">
                    <div>
                        <label for="detail-quantity" class="mb-1 block text-sm font-medium">Quantity</label>
                        <input type="number" min="0" id="detail-quantity" wire:model="quantity" class="w-full rounded border px-3 py-2">
                        @error('quantity') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="detail-unit" class="mb-1 block text-sm font-medium">Satuan</label>
                        <input type="text" id="detail-unit" wire:model="unit" class="w-full rounded border px-3 py-2">
                        @error('unit') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="detail-min-stock" class="mb-1 block text-sm font-medium">Min. Stok</label>
                        <input type="number" min="0" id="detail-min-stock" wire:model="min_stock" class="w-full rounded border px-3 py-2">
                        @error('min_stock') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="detail-price" class="mb-1 block text-sm font-medium">Harga</label>
                        <input type="number" min="0" step="0.01" id="detail-price" wire:model="price" class="w-full rounded border px-3 py-2">
                        @error('price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Deskripsi (Markdown)</label>
                    {{-- Toast UI Editor; isinya disinkronkan ke properti Livewire "description" --}}
                    <div wire:ignore
                         x-data="{ editor: null }"
                         x-init="editor = new toastui.Editor({
                             el: $refs.editor,
                             height: '320px',
                             initialEditType: 'markdown',
                             previewStyle: 'vertical',
                             initialValue: $wire.description ?? '',
                             events: { change: () => { $wire.description = editor.getMarkdown() } },
                         })">
                        <div x-ref="editor"></div>
                    </div>
                    @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="cancelEdit" class="rounded border px-4 py-2 text-sm">Batal</button>
                    <button type="submit" id="detail-save" class="rounded bg-indigo-600 px-4 py-2 text-sm text-white">Simpan</button>
                </div>
            </form>
        </section>
    @endif
</div>