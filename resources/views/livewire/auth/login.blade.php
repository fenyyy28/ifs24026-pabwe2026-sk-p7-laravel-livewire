<div class="mx-auto mt-12 max-w-md rounded-lg bg-white p-8 shadow">
    <h1 class="mb-6 text-2xl font-bold">Login</h1>

    <form wire:submit="login" class="space-y-4">
        <div>
            <label for="login-username" class="mb-1 block text-sm font-medium">Username</label>
            <input type="text" id="login-username" wire:model="username" autocomplete="username"
                   class="w-full rounded border px-3 py-2 focus:outline-none focus:ring">
            @error('username') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="login-password" class="mb-1 block text-sm font-medium">Password</label>
            <input type="password" id="login-password" wire:model="password" autocomplete="current-password"
                   class="w-full rounded border px-3 py-2 focus:outline-none focus:ring">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="remember"> Ingat saya
        </label>

        <button type="submit" id="login-submit"
                class="w-full rounded bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700">
            Masuk
        </button>
    </form>

    <p class="mt-4 text-center text-sm">
        Belum punya akun? <a href="{{ route('register') }}" class="font-medium text-indigo-700 underline hover:text-indigo-900">Daftar</a>
    </p>
</div>
