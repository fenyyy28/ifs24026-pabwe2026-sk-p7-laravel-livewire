<?php

use App\Features\Admin\Livewire\AdminUserStockList;
use App\Features\Admin\Livewire\UserList;
use App\Features\Auth\Livewire\Login;
use App\Features\Auth\Livewire\Register;
use App\Features\Profile\Livewire\Profile;
use App\Features\Stock\Livewire\StockDetail;
use App\Features\Stock\Livewire\StockList;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Guest melihat halaman login langsung di "/" (HTTP 200).
// User yang sudah login diarahkan sesuai peran oleh middleware guest
// (lihat redirectUsersTo di bootstrap/app.php): admin -> users, member -> stocks.
Route::get('/', Login::class)->middleware('guest')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', function () {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');

    Route::get('/profile', Profile::class)->name('profile');

    // Member: kelola stok miliknya sendiri
    Route::middleware(['role:member', 'owns.stock'])->group(function () {
        Route::get('/stocks', StockList::class)->name('stocks.index');
        Route::get('/stocks/{stock}', StockDetail::class)->name('stocks.show');
    });

    // Admin: read-only
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', UserList::class)->name('users');
        Route::get('/users/{user}/stocks', AdminUserStockList::class)->name('users.stocks');
    });
});