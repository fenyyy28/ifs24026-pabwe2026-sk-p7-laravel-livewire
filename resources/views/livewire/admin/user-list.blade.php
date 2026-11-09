<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Daftar User</h1>
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama / username..."
               class="w-64 rounded border px-3 py-2 text-sm">
    </div>

    <div class="overflow-x-auto rounded-lg bg-white shadow">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100 text-left">
                <tr>
                    <th class="px-4 py-2">Nama</th>
                    <th class="px-4 py-2">Username</th>
                    <th class="px-4 py-2">Role</th>
                    <th class="px-4 py-2 text-right">Jumlah Item</th>
                    <th class="px-4 py-2 text-right">Total Qty</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $user->name }}</td>
                        <td class="px-4 py-2">{{ $user->username }}</td>
                        <td class="px-4 py-2">{{ $user->role }}</td>
                        <td class="px-4 py-2 text-right">{{ $user->stocks_count }}</td>
                        <td class="px-4 py-2 text-right">{{ (int) $user->stocks_quantity_sum }}</td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('admin.users.stocks', $user) }}" class="text-indigo-600 hover:underline">Lihat stok</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">Tidak ada user.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
</div>
