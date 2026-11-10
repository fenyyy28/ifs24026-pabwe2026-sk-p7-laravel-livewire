<div class="mx-auto max-w-2xl space-y-6">
    <h1 class="text-2xl font-bold">Profil</h1>

    @if (session('status'))
        <div class="rounded bg-green-100 px-4 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    {{-- Foto --}}
    <section class="rounded-lg bg-white p-6 shadow">
        <h2 class="mb-4 font-semibold">Foto Profil</h2>
        <div class="flex items-center gap-4">
            @if ($user->profilePhotoUrl())
                <img src="{{ $user->profilePhotoUrl() }}" alt="Foto profil" class="h-20 w-20 rounded-full object-cover">
            @else
                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-gray-200 text-2xl">👤</div>
            @endif

            <form wire:submit="savePhoto" class="space-y-2">
                <input type="file" wire:model="photo" accept="image/*" id="profile-photo" aria-label="Pilih foto profil">
                @error('photo') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                <div class="flex gap-2">
                    <button type="submit" class="rounded bg-indigo-600 px-3 py-1 text-sm text-white">Simpan Foto</button>
                    @if ($user->profilePhotoUrl())
                        <button type="button" wire:click="removePhoto" class="rounded border px-3 py-1 text-sm">Hapus</button>
                    @endif
                </div>
            </form>
        </div>
    </section>

    {{-- Data --}}
    <section class="rounded-lg bg-white p-6 shadow">
        <h2 class="mb-4 font-semibold">Ubah Data</h2>
        <form wire:submit="saveProfile" class="space-y-4">
            <div>
                <label for="profile-name" class="mb-1 block text-sm font-medium">Nama</label>
                <input type="text" id="profile-name" wire:model="name" class="w-full rounded border px-3 py-2">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="profile-username" class="mb-1 block text-sm font-medium">Username</label>
                <input type="text" id="profile-username" wire:model="username" class="w-full rounded border px-3 py-2">
                @error('username') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-white">Simpan</button>
        </form>
    </section>

    {{-- Password --}}
    <section class="rounded-lg bg-white p-6 shadow">
        <h2 class="mb-4 font-semibold">Ubah Password</h2>
        <form wire:submit="savePassword" class="space-y-4">
            <div>
                <label for="profile-current-password" class="mb-1 block text-sm font-medium">Password Saat Ini</label>
                <input type="password" id="profile-current-password" wire:model="current_password" class="w-full rounded border px-3 py-2">
                @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="profile-password" class="mb-1 block text-sm font-medium">Password Baru</label>
                <input type="password" id="profile-password" wire:model="password" class="w-full rounded border px-3 py-2">
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="profile-password-confirmation" class="mb-1 block text-sm font-medium">Konfirmasi Password Baru</label>
                <input type="password" id="profile-password-confirmation" wire:model="password_confirmation" class="w-full rounded border px-3 py-2">
            </div>
            <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-white">Ubah Password</button>
        </form>
    </section>
</div>
