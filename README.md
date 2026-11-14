# Perpustakaan Digital Mei

Aplikasi inventaris dan peminjaman perpustakaan dengan dua project Laravel: REST API di root repository dan frontend Blade di folder `frontend/`. Frontend tidak membaca database API secara langsung; semua data buku dan pinjaman lewat HTTP API dan token Sanctum disimpan di session server frontend.

## Kebutuhan

- PHP 8.3+ dengan ekstensi `pdo_mysql`, Composer, Node.js/npm.
- MySQL 5.7+ atau MariaDB 10.3+.
- Buat database `mei_library` untuk API dan `mei_library_frontend` untuk project frontend.

## Menjalankan backend API

Dari direktori repository:

```sh
composer install
copy .env.example .env
php artisan key:generate
```

Atur `DB_HOST`, `DB_PORT`, `DB_DATABASE=mei_library`, `DB_USERNAME`, dan `DB_PASSWORD` di `.env`, lalu jalankan:

```sh
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Seeder menambahkan delapan judul dari empat genre dan akun demo `admin@meilibrary.local` dengan password `password`. Gunakan hanya untuk lokal; ganti/hapus password demo untuk penggunaan bersama.

## Menjalankan frontend Blade

Buka terminal kedua:

```sh
cd frontend
composer install
copy .env.example .env
php artisan key:generate
```

Atur `LIBRARY_API_URL=http://127.0.0.1:8000`, koneksi MySQL frontend, dan `APP_URL=http://127.0.0.1:8001` di `frontend/.env`. Jalankan:

```sh
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8001
```

Buka `http://127.0.0.1:8001`. Masuk dengan akun demo atau pilih **Daftar**. Token API hanya dikirim server ke server dan tidak ditanam di HTML/browser.

## Fitur

- Register, login, logout token Sanctum; endpoint register/login publik, seluruh endpoint buku dan pinjaman dilindungi `auth:sanctum`.
- Tambah/hapus buku, filter genre, sort judul atau stok naik/turun, dan stok nol tetap tampil.
- Satu transaksi berisi beberapa judul dan jumlah berbeda. Ringkasan dapat ditinjau, dikembalikan untuk diedit, atau dibatalkan tanpa mengubah stok.
- Konfirmasi memvalidasi ulang semua stok dalam transaksi database dan mengurangi stok hanya sesudah semua judul lolos. Kunci idempotensi mencegah pengiriman ulang menggandakan transaksi.
- Riwayat menyimpan snapshot judul, peminjam, telepon, tanggal jatuh tempo, dan jumlah; tetap terbaca setelah buku dihapus.
- Validasi nama, telepon, tanggal, ID buku, jumlah bulat positif, dan stok; kegagalan transaksi tidak mengubah stok.

## Uji API melalui Postman

Import `postman/Mei-Library.postman_collection.json`. Pastikan server API berjalan. Jalankan request sesuai urutan folder: **Auth**, **Books**, **Loans**, **Cleanup**, lalu **Logout**. Login valid menyimpan token collection otomatis. Akun seeder disiapkan untuk login; request register menggunakan email contoh yang perlu diganti dengan email unik bila pernah dipakai.

Skenario mencakup register/login valid dan invalid, akses tanpa token, tambah/hapus/daftar buku, filter genre dan sort stok, peminjaman valid, kuantitas di luar stok, konfirmasi idempoten, logout, serta pemakaian token yang telah dicabut. Sebelum dan sesudah request pinjaman gagal, panggil daftar buku dan pastikan nilai stok sama. Setelah peminjaman berhasil, pastikan stok berkurang sesuai jumlah item.

## Kontrak API dan dokumentasi

Rincian endpoint, field, status HTTP, bentuk respons/error, serta diagram hubungan service tersedia pada [docs/api-contract.md](docs/api-contract.md). Semua endpoint menerima dan mengembalikan JSON. Daftar endpoint:

| Method | Endpoint | Auth |
|---|---|---|
| POST | `/api/auth/register` | Publik |
| POST | `/api/auth/login` | Publik |
| POST | `/api/auth/logout` | Bearer token |
| GET, POST | `/api/books` | Bearer token |
| DELETE | `/api/books/{id}` | Bearer token |
| GET, POST | `/api/loans` | Bearer token |

## Keamanan dan pengumpulan

Jangan commit `.env`, token API, password produksi, atau kredensial. Simpan repository GitLab sebagai **private**, lalu berikan akses Developer ke alamat yang diminta penyelenggara secara manual. Jangan menaruh logo/nama perusahaan pada aplikasi. Ambil screenshot halaman koleksi, ringkasan peminjaman, histori, serta bukti respons Postman setelah menjalankan skenario lokal.

Opsional: backend dan frontend bisa dijalankan pada image Docker terpisah; koneksi database tetap berasal dari environment. Proyek ini belum menyertakan konfigurasi Docker.
