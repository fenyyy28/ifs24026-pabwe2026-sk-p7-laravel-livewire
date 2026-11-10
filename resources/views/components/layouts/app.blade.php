<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Stock Barang' }} - {{ config('app.name') }}</title>

    <meta name="description" content="Aplikasi inventaris stok barang: kelola stok, adjust quantity, import/export Excel, dan generate item dengan AI.">

    {{-- Tailwind di-build lokal lewat Vite (tanpa CDN), supaya halaman cepat & tidak bergantung pihak ketiga --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 text-gray-800">
    @auth
        <nav class="border-b bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
                <div class="flex items-center gap-6">
                    <a href="{{ route('home') }}" class="text-lg font-bold text-indigo-600">📦 Stock Barang</a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.users') }}" class="text-sm hover:text-indigo-600">Users</a>
                    @else
                        <a href="{{ route('stocks.index') }}" class="text-sm hover:text-indigo-600">Stok</a>
                    @endif
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('profile') }}" class="flex items-center gap-2 text-sm hover:text-indigo-600">
                        @if (auth()->user()->profilePhotoUrl())
                            <img src="{{ auth()->user()->profilePhotoUrl() }}" alt="Foto profil" class="h-8 w-8 rounded-full object-cover">
                        @endif
                        <span>{{ auth()->user()->name }}</span>
                        <span class="rounded bg-gray-100 px-1.5 py-0.5 text-xs">{{ auth()->user()->role }}</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" id="logout-submit" class="text-sm text-red-600 hover:underline">Logout</button>
                    </form>
                </div>
            </div>
        </nav>
    @endauth

    <main class="mx-auto max-w-6xl px-4 py-6">
        {{ $slot }}
    </main>

    {!! str_replace('<script ', '<script defer ', \Illuminate\Support\Facades\Blade::render('@@livewireScripts')) !!}
</body>
</html>