# Aplikasi Stock Barang — Laravel 13 + Livewire (Feature-First)

Studi Kasus C (P7): inventaris stok barang. Pola sama dengan latihan Todolist
(Auth, Profil, Admin, Excel, AI, coverage 100%, Jenkins) — domain Todo → **Stock**.

## Peran
| Peran | Kemampuan |
|---|---|
| Guest | Login / register; `/` → login |
| Member | Kelola stok miliknya sendiri (CRUD, adjust qty, cover, Markdown, Excel, AI) |
| Admin | Lihat daftar user + stok tiap user (**read-only**) |

Akun seeder: `admin / admin` dan `member / member` (+ 5 contoh stok).

## Setup
```bash
# 1. Penamaan folder: {username}-pabwe2026-sk-p7-laravel-livewire
composer install
cp .env.example .env
php artisan key:generate

# 2. Buat database MySQL kosong: pabwe_p7_stock  (sesuaikan DB_USERNAME/DB_PASSWORD di .env)
php artisan migrate --seed
php artisan storage:link

php artisan serve
```
Isi `OPENAI_API_KEY` di `.env` untuk fitur Generate AI (jangan di-commit).
Set `FORCE_HTTPS=true` di produksi.

## Test, coverage & audit
Butuh ekstensi PHP: `pdo_sqlite`, `gd`, dan **pcov** atau **xdebug** (untuk coverage).
```bash
composer test                 # semua test
composer test:coverage        # php artisan test --coverage --min=100
composer audit                # harus bersih
```

## Catatan desain
- Status stok **dihitung otomatis**: `quantity <= 0` → `out_of_stock`; `quantity <= min_stock` → `low_stock`; selain itu `available`.
  Berlaku saat create, edit, adjust (+/−), import Excel, dan simpan hasil AI.
- Ownership berlapis: middleware `role` + `owns.stock`, `StockPolicy`, dan `Gate::authorize` di setiap aksi Livewire.
- AI dipisah lewat `AiClientInterface` sehingga di test cukup di-`createStub`.
- Tailwind & Toast UI Editor dimuat via CDN (tanpa `npm build`).
- Excel: header `name, sku, qty, unit, price, min_stock, status, description` (kolom status saat impor diabaikan & dihitung ulang).
