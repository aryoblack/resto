# Tasks — Aplikasi Restoran (resto-app)

## Task List Implementasi

---

### 1. Setup Proyek & Konfigurasi Awal

- [x] 1.1 Inisialisasi proyek Laravel 13 baru di direktori `e:\laragon\www\resto`
- [x] 1.2 Konfigurasi file `.env` (database, Redis, mail, app URL)
- [x] 1.3 Install dependensi utama via Composer:
  - `laravel/sanctum` (autentikasi API)
  - `laravel/reverb` (WebSocket server)
  - `spatie/laravel-permission` (RBAC)
  - `simplesoftwareio/simple-qrcode` (QR code generator)
  - `maatwebsite/excel` (export Excel)
  - `barryvdh/laravel-dompdf` (export PDF)
  - `intervention/image` (optimasi gambar)
  - `giorgiosironi/eris` (property-based testing)
- [x] 1.4 Install dependensi frontend via NPM (Vite, Tailwind CSS, Alpine.js, Laravel Echo)
- [x] 1.5 Konfigurasi Tailwind CSS dengan warna custom dari UI Style Guide (`#FF7A2F`, `#2EC4B6`, dll.)
- [x] 1.6 Konfigurasi Laravel Sanctum untuk autentikasi API
- [x] 1.7 Konfigurasi Laravel Reverb untuk WebSocket
- [x] 1.8 Konfigurasi Redis untuk cache, session, dan queue
- [x] 1.9 Setup Laravel Horizon untuk queue worker monitoring
- [x] 1.10 Konfigurasi storage untuk upload gambar (local/S3)

---

### 2. Migrasi Database & Model

- [x] 2.1 Buat migrasi tabel `users` (tambah kolom `role`, `poin`, `is_active`, `failed_login_attempts`, `locked_until`)
- [x] 2.2 Buat migrasi tabel `categories` (`id`, `name`, `sort_order`)
- [x] 2.3 Buat migrasi tabel `menus` (`id`, `name`, `category_id`, `price`, `stock`, `description`, `image_url`, `is_available`, `deleted_at`)
- [x] 2.4 Buat migrasi tabel `variants` (`id`, `menu_id`, `variant_name`, `extra_price`)
- [x] 2.5 Buat migrasi tabel `tables` (`id`, `table_number`, `qr_code`, `status`)
- [x] 2.6 Buat migrasi tabel `orders` (`id`, `user_id`, `table_id`, `total_price`, `discount_amount`, `tax_amount`, `service_charge`, `payment_method`, `payment_status`, `order_status`, `order_type`, `voucher_code`, `notes`)
- [x] 2.7 Buat migrasi tabel `order_items` (`id`, `order_id`, `menu_id`, `quantity`, `variant_selected`, `note`, `price_at_time`)
- [x] 2.8 Buat migrasi tabel `reservations` (`id`, `user_id`, `table_id`, `date`, `time`, `number_of_people`, `status`, `notes`)
- [x] 2.9 Buat migrasi tabel `inventory` (`id`, `ingredient_name`, `unit`, `current_stock`, `min_stock`, `supplier`)
- [x] 2.10 Buat migrasi tabel `stock_movements` (`id`, `ingredient_id`, `quantity_change`, `type`, `note`, `order_id`, `created_by`)
- [x] 2.11 Buat migrasi tabel `promos` (`id`, `name`, `code`, `type`, `value`, `min_purchase`, `max_discount`, `start_date`, `end_date`, `is_active`, `usage_limit`, `usage_count`)
- [x] 2.12 Buat migrasi tabel `voucher_usages` (`id`, `promo_id`, `user_id`, `order_id`, `discount_applied`)
- [x] 2.13 Buat migrasi tabel `point_transactions` (`id`, `user_id`, `order_id`, `type`, `points`, `balance_after`, `note`)
- [x] 2.14 Buat migrasi tabel `ratings` (`id`, `order_id`, `user_id`, `rating`, `review`)
- [x] 2.15 Buat migrasi tabel `system_settings` (`id`, `key`, `value`)
- [x] 2.16 Buat migrasi tabel `menu_ingredient_map` (`id`, `menu_id`, `ingredient_id`, `quantity_used`)
- [x] 2.17 Buat semua indeks database yang direkomendasikan di design.md
- [x] 2.18 Buat Eloquent Model untuk setiap tabel dengan relasi, fillable, dan casts yang tepat
- [x] 2.19 Buat database seeder untuk data awal (admin default, kategori contoh, pengaturan sistem default)

---

### 3. Auth_System — Autentikasi & Otorisasi

- [x] 3.1 Setup Spatie Laravel Permission (roles: `customer`, `waiter`, `chef`, `admin`)
- [x] 3.2 Buat `AuthController` dengan method `login`, `logout`, `register`
- [x] 3.3 Implementasi login dengan validasi email & password, return Sanctum token
- [x] 3.4 Implementasi rate limiting login: kunci akun 15 menit setelah 5 kali gagal
- [x] 3.5 Implementasi logout yang menginvalidasi token Sanctum aktif
- [x] 3.6 Implementasi registrasi pelanggan dengan validasi (format email, keunikan, password min 8 karakter)
- [x] 3.7 Buat middleware `CheckRole` untuk RBAC pada setiap route group
- [x] 3.8 Konfigurasi route groups dengan middleware role:
  - `/api/customer/*` → role `customer`
  - `/api/staff/kds` → role `chef`, `admin`
  - `/api/staff/orders` → role `waiter`, `chef`, `admin`
  - `/api/admin/*` → role `admin`
- [x] 3.9 Buat `StaffController` untuk admin membuat akun karyawan (waiter/chef) dan kirim email kredensial
- [x] 3.10 Implementasi nonaktifkan akun karyawan (revoke semua token Sanctum aktif)
- [x] 3.11 Buat Form Request classes untuk validasi: `LoginRequest`, `RegisterRequest`, `CreateStaffRequest`
- [x] 3.12 Tulis unit test untuk `AuthSystem` (login valid, login invalid, lockout, logout, RBAC)

---

### 4. Menu_Manager — Manajemen Menu, Kategori & Varian

- [x] 4.1 Buat `MenuController` (admin) dengan CRUD lengkap
- [x] 4.2 Implementasi upload gambar menu (validasi JPG/PNG/WebP, maks 2MB, optimasi dengan Intervention Image)
- [x] 4.3 Implementasi soft delete menu dengan penyimpanan riwayat penghapusan
- [x] 4.4 Implementasi toggle status menu (Aktif/Nonaktif) dengan cache invalidation
- [x] 4.5 Buat `CategoryController` (admin) dengan CRUD dan fitur reorder (`sort_order`)
- [x] 4.6 Buat `VariantController` (admin) dengan CRUD varian per menu
- [x] 4.7 Buat `MenuApiController` (customer) untuk mengambil menu aktif dengan filter kategori dan pencarian
- [x] 4.8 Implementasi caching menu dengan Redis (TTL 5 menit), invalidasi saat ada perubahan
- [x] 4.9 Buat Form Request classes: `CreateMenuRequest`, `UpdateMenuRequest`
- [x] 4.10 Tulis unit test untuk `MenuManager` (CRUD, validasi field wajib, toggle status)

---

### 5. QR_Scanner — Manajemen Meja & QR Code

- [x] 5.1 Buat `TableController` (admin) dengan CRUD meja
- [x] 5.2 Implementasi generate QR code unik saat meja dibuat (menggunakan `simplesoftwareio/simple-qrcode`)
- [x] 5.3 QR code berisi URL yang menyertakan `table_id` dan token validasi
- [x] 5.4 Implementasi regenerasi QR code (nonaktifkan QR lama, buat QR baru)
- [x] 5.5 Buat endpoint `GET /scan/{qrCode}` untuk validasi QR code dan redirect ke Customer_App
- [x] 5.6 Implementasi update status meja otomatis (`available`/`occupied`) berdasarkan scan dan penyelesaian pesanan
- [x] 5.7 Implementasi perubahan status meja manual oleh admin
- [x] 5.8 Tulis unit test untuk `QRScanner` (generate unik, validasi round-trip, QR tidak valid)

---

### 6. Order_Manager — Siklus Hidup Pesanan

- [x] 6.1 Buat `OrderController` dengan method `store`, `updateStatus`, `index`, `show`
- [x] 6.2 Implementasi pembuatan pesanan dari keranjang (validasi stok, simpan `price_at_time`, buat `order_items`)
- [x] 6.3 Implementasi state machine status pesanan: `Diterima` → `Diproses` → `Dimasak` → `Selesai` → `Disajikan` (dengan validasi urutan)
- [x] 6.4 Implementasi pembatalan pesanan (hanya admin, kembalikan stok)
- [x] 6.5 Implementasi kalkulasi total pesanan: `Σ(price_at_time × qty) + tax + service_charge - discount`
- [x] 6.6 Implementasi penolakan pesanan jika stok tidak mencukupi (return daftar item bermasalah)
- [x] 6.7 Implementasi update status meja otomatis ke `available` saat semua pesanan di meja berstatus `Disajikan` dan `paid`
- [x] 6.8 Broadcast event `OrderCreated` dan `OrderStatusUpdated` via Laravel Reverb
- [x] 6.9 Buat `KitchenOrderController` untuk KDS (ambil pesanan dengan status `Dimasak`)
- [x] 6.10 Buat Form Request: `CreateOrderRequest`, `UpdateOrderStatusRequest`
- [x] 6.11 Tulis unit test untuk `OrderManager` (pembuatan pesanan, state machine, kalkulasi total, penolakan stok)

---

### 7. Payment_Gateway — Pembayaran

- [x] 7.1 Buat `PaymentController` dengan method `initiate`, `webhook`, `confirmCash`
- [x] 7.2 Integrasi Midtrans atau Xendit untuk pembayaran QRIS dan kartu
- [x] 7.3 Implementasi generate QRIS dinamis unik per transaksi
- [x] 7.4 Implementasi handler webhook dari payment provider (verifikasi signature, update status pesanan)
- [x] 7.5 Implementasi konfirmasi pembayaran tunai oleh pelayan (update `payment_status` ke `paid`)
- [x] 7.6 Implementasi trigger akumulasi poin setelah pembayaran berhasil
- [x] 7.7 Implementasi pencatatan riwayat transaksi pembayaran
- [x] 7.8 Gunakan database transaction untuk: update order + tambah poin + catat `point_transaction`
- [x] 7.9 Tulis feature test untuk alur pembayaran (QRIS, tunai, webhook handling)

---

### 8. Stock_Manager — Inventaris & Stok

- [x] 8.1 Buat `InventoryController` (admin) dengan CRUD bahan baku
- [x] 8.2 Implementasi pencatatan pemasukan stok (`addStock`) dengan record `stock_movements` tipe `in`
- [x] 8.3 Implementasi pengurangan stok otomatis saat pesanan dibuat (`deductStock`) dengan record `stock_movements` tipe `out`
- [x] 8.4 Implementasi deteksi stok kritis (`current_stock` ≤ `min_stock`) dan trigger notifikasi
- [x] 8.5 Implementasi validasi input stok (tolak nilai negatif atau non-angka)
- [x] 8.6 Buat endpoint untuk riwayat pergerakan stok per bahan baku
- [x] 8.7 Gunakan database transaction untuk update `inventory` + catat `stock_movements`
- [x] 8.8 Tulis unit test untuk `StockManager` (addStock, deductStock, deteksi stok kritis, validasi input)

---

### 9. Notification_Service — Notifikasi Real-time

- [x] 9.1 Konfigurasi Laravel Reverb sebagai WebSocket server
- [x] 9.2 Konfigurasi Laravel Echo di frontend untuk subscribe ke channels
- [x] 9.3 Buat event classes: `OrderCreated`, `OrderStatusUpdated`, `StockCritical`, `ReservationUpdated`
- [x] 9.4 Definisikan broadcast channels di `routes/channels.php`:
  - `orders` (public) → Admin, Waiter, Chef
  - `kitchen` (public) → Chef
  - `customer.{userId}` (private) → Customer
  - `admin` (private) → Admin
- [x] 9.5 Implementasi notifikasi pesanan baru ke Admin_Dashboard dan KDS (< 5 detik)
- [x] 9.6 Implementasi notifikasi perubahan status pesanan ke Customer_App
- [x] 9.7 Implementasi notifikasi stok kritis ke Admin_Dashboard
- [x] 9.8 Implementasi push notification untuk Customer PWA (Web Push API / service worker)
- [x] 9.9 Implementasi auto-reconnect WebSocket dengan exponential backoff (1s, 2s, 4s, maks 30s)
- [x] 9.10 Tampilkan indikator status koneksi WebSocket di UI

---

### 10. Promo_Engine — Promosi & Voucher

- [x] 10.1 Buat `PromoController` (admin) dengan CRUD promosi
- [x] 10.2 Implementasi tipe promosi: diskon persentase dan diskon nominal
- [x] 10.3 Implementasi validasi voucher: cek kedaluwarsa, keunikan kode, minimum pembelian, `max_discount`
- [x] 10.4 Implementasi penerapan voucher pada pesanan (kalkulasi diskon, update `discount_amount`)
- [x] 10.5 Implementasi pencatatan penggunaan voucher di tabel `voucher_usages`
- [x] 10.6 Buat endpoint `POST /api/voucher/validate` untuk validasi kode voucher dari keranjang
- [x] 10.7 Buat endpoint untuk mengambil promosi aktif (untuk banner Customer_App)
- [x] 10.8 Tulis unit test untuk `PromoEngine` (validasi voucher valid, kedaluwarsa, minimum tidak terpenuhi, kalkulasi diskon)

---

### 11. Loyalty_Engine — Program Poin

- [x] 11.1 Buat `LoyaltyController` untuk endpoint saldo poin dan riwayat
- [x] 11.2 Implementasi akumulasi poin setelah pembayaran berhasil: `floor(total / point_conversion_rate)`
- [x] 11.3 Implementasi penukaran poin sebagai diskon saat checkout
- [x] 11.4 Implementasi validasi saldo poin (tolak penukaran jika saldo tidak cukup)
- [x] 11.5 Catat setiap transaksi poin di tabel `point_transactions` (earn/redeem, balance_after)
- [x] 11.6 Gunakan database transaction untuk penukaran poin + update saldo + catat transaksi
- [x] 11.7 Tulis unit test untuk `LoyaltyEngine` (akumulasi proporsional, penukaran valid, penolakan saldo kurang, round-trip saldo)

---

### 12. Reservation_Manager — Reservasi Meja

- [x] 12.1 Buat `ReservationController` dengan method `store`, `confirm`, `cancel`, `index`
- [x] 12.2 Implementasi pembuatan reservasi dengan validasi konflik (meja + tanggal + waktu yang sama)
- [x] 12.3 Implementasi konfirmasi reservasi oleh admin (update status ke `confirmed`, kirim notifikasi)
- [x] 12.4 Implementasi pembatalan reservasi (update status ke `cancelled`, kirim notifikasi)
- [x] 12.5 Buat endpoint untuk cek ketersediaan meja berdasarkan tanggal dan waktu
- [x] 12.6 Kirim email notifikasi ke pelanggan saat reservasi dikonfirmasi atau dibatalkan
- [x] 12.7 Tulis feature test untuk `ReservationManager` (buat reservasi, konflik, konfirmasi, pembatalan)

---

### 13. Report_Engine — Laporan & Analitik

- [x] 13.1 Buat `ReportController` (admin) dengan method untuk setiap jenis laporan
- [x] 13.2 Implementasi laporan penjualan dengan filter harian/mingguan/bulanan
- [x] 13.3 Implementasi kalkulasi metrik: total omzet, jumlah pesanan, rata-rata nilai pesanan, menu terlaris, rincian per metode pembayaran
- [x] 13.4 Implementasi laporan stok opname (kondisi stok saat ini vs stok minimal)
- [x] 13.5 Implementasi export laporan ke Excel menggunakan `maatwebsite/excel`
- [x] 13.6 Implementasi export laporan ke PDF menggunakan `barryvdh/laravel-dompdf`
- [x] 13.7 Queue job untuk export laporan besar (notifikasi via email saat selesai)
- [x] 13.8 Implementasi data untuk grafik pendapatan per jam (untuk dashboard)
- [x] 13.9 Implementasi endpoint dashboard metrics: pendapatan hari ini, jumlah pesanan, jumlah pelanggan, stok kritis
- [x] 13.10 Tulis feature test untuk `ReportEngine` (laporan penjualan, stok, export)

---

### 14. Rating & Ulasan

- [x] 14.1 Buat `RatingController` dengan method `store` dan `index`
- [x] 14.2 Implementasi penyimpanan rating (1-5 bintang) dan ulasan teks opsional per pesanan
- [x] 14.3 Implementasi validasi: satu rating per pesanan (idempoten), rentang nilai 1-5
- [x] 14.4 Tampilkan prompt rating di Customer_App saat status pesanan berubah ke `Disajikan`
- [x] 14.5 Buat endpoint untuk ringkasan rating rata-rata dan daftar ulasan terbaru (untuk Admin_Dashboard)
- [x] 14.6 Tulis unit test untuk `Rating` (idempoten, validasi rentang, satu rating per pesanan)

---

### 15. Customer PWA — Antarmuka Pelanggan

- [x] 15.1 Setup PWA (manifest.json, service worker, offline support dasar)
- [x] 15.2 Buat halaman Beranda: banner promo, filter kategori, grid menu (foto, nama, harga, rating, tombol +)
- [x] 15.3 Implementasi filter kategori dan pencarian menu real-time (< 1 detik)
- [x] 15.4 Tampilkan label "Habis" dan nonaktifkan tombol untuk menu stok 0 atau nonaktif
- [x] 15.5 Buat halaman Detail Menu: foto besar, deskripsi, pilihan varian dengan harga tambahan
- [x] 15.6 Implementasi keranjang belanja: tambah/ubah/hapus item, catatan per item
- [x] 15.7 Buat halaman Keranjang: daftar item, input kode voucher, input penukaran poin, subtotal, diskon, total
- [x] 15.8 Buat halaman Checkout: pilih metode pembayaran (tunai/QRIS/kartu), konfirmasi pesanan
- [x] 15.9 Tampilkan QRIS dinamis untuk pembayaran digital
- [x] 15.10 Buat halaman Tracking Pesanan: indikator tahapan visual (Diterima → Dimasak → Selesai → Disajikan), update real-time via WebSocket
- [x] 15.11 Buat halaman Profil: saldo poin, riwayat poin, riwayat pesanan
- [x] 15.12 Buat halaman Reservasi: form reservasi meja (tanggal, waktu, jumlah orang, pilih meja)
- [x] 15.13 Implementasi tombol floating "Scan Meja" (menggunakan kamera browser / redirect ke URL QR)
- [x] 15.14 Implementasi push notification untuk update status pesanan
- [x] 15.15 Terapkan UI Style Guide: font Poppins/Inter, warna primary `#FF7A2F`, card radius 16px, tombol pill 30px

---

### 16. Admin Dashboard — Antarmuka Admin/Manajer

- [x] 16.1 Buat layout Admin_Dashboard dengan sidebar navigasi (semua menu dari konsep)
- [x] 16.2 Buat halaman Dashboard utama: kartu metrik (pendapatan, pesanan, pelanggan, stok kritis), grafik pendapatan per jam, daftar pesanan masuk real-time
- [x] 16.3 Buat halaman Manajemen Menu: tabel menu dengan foto/nama/kategori/harga/stok/status, modal tambah/edit menu
- [x] 16.4 Buat halaman Manajemen Kategori: tabel kategori, drag-and-drop reorder
- [~] 16.5 Buat halaman Manajemen Varian/Topping: tabel varian per menu
- [x] 16.6 Buat halaman Manajemen Meja: grid/tabel meja dengan status, QR code (download/print), tambah/edit meja
- [x] 16.7 Buat halaman Manajemen Stok: tabel bahan baku dengan indikator stok kritis, form tambah stok, riwayat pergerakan
- [x] 16.8 Buat halaman Manajemen Karyawan: tabel karyawan, form tambah karyawan, ubah role, nonaktifkan akun
- [x] 16.9 Buat halaman Pesanan Live: daftar pesanan real-time dengan filter (dine-in/delivery/status/meja), tombol ubah status, notifikasi visual & suara pesanan baru
- [x] 16.10 Buat halaman Promo & Voucher: tabel promosi, form buat/edit promosi, riwayat penggunaan voucher
- [x] 16.11 Buat halaman Laporan: filter tanggal, laporan penjualan, laporan stok opname, tombol export Excel/PDF
- [x] 16.12 Buat halaman Reservasi: tabel reservasi dengan filter tanggal/status, tombol konfirmasi/batalkan
- [x] 16.13 Buat halaman Pengaturan: form nama restoran, logo, jam operasional, konversi poin, pajak, biaya layanan
- [x] 16.14 Implementasi notifikasi real-time di Admin_Dashboard via Laravel Echo (pesanan baru, stok kritis)
- [x] 16.15 Terapkan UI Style Guide konsisten dengan Customer_App

---

### 17. KDS — Kitchen Display System

- [x] 17.1 Buat layout KDS khusus tablet (landscape)
- [x] 17.2 Tampilkan antrean pesanan dalam bentuk kartu (Board style)
- [x] 17.3 Implementasi pemisahan item berdasarkan stasiun (Dapur/Bar)
- [x] 17.4 Implementasi timer pada setiap kartu pesanan (hijau < 15 mnt, kuning 15-25 mnt, merah > 25 mnt)
- [x] 17.5 Tombol interaktif per item: "Sedang Dimasak", "Selesai"
- [x] 17.6 Update real-time via WebSocket agar selaras dengan Pesanan Live di Admin dan Tracking di PWA

---

### 18. Pengaturan Sistem

- [x] 18.1 Buat `SettingController` (admin) dengan method `index` dan `update`
- [x] 18.2 Implementasi penyimpanan dan pengambilan pengaturan dari tabel `system_settings`
- [x] 18.3 Cache pengaturan sistem di Redis, invalidasi saat ada perubahan
- [x] 18.4 Implementasi konfigurasi: nama restoran, logo, jam operasional, informasi kontak
- [x] 18.5 Implementasi konfigurasi nilai konversi poin (key: `point_conversion_rate`)
- [x] 18.6 Implementasi konfigurasi persentase pajak dan biaya layanan
- [x] 18.7 Pastikan perubahan pengaturan diterapkan ke seluruh tampilan dalam < 10 detik

---

### 19. Property-Based Tests (PBT)

- [x] 19.1 Setup library Eris dan konfigurasi PHPUnit untuk property tests (minimal 100 iterasi per property)
- [x] 19.2 **Property 1** — RBAC: tulis test bahwa setiap role hanya dapat mengakses resource yang diizinkan
- [x] 19.3 **Property 2** — Login-Logout Round Trip: tulis test bahwa token setelah logout selalu tidak valid
- [x] 19.4 **Property 3** — Validasi Registrasi: tulis test konsistensi validasi email/password untuk semua kombinasi input
- [x] 19.5 **Property 4** — QR Code Unik: tulis test bahwa setiap meja memiliki QR code unik dan scan selalu mengembalikan `table_id` yang benar
- [x] 19.6 **Property 5** — Menu Nonaktif: tulis test bahwa menu nonaktif/stok 0 tidak pernah muncul di Customer_App
- [x] 19.7 **Property 6** — Validasi Menu: tulis test bahwa menu tanpa field wajib selalu ditolak
- [x] 19.8 **Property 7** — Kalkulasi Total Pesanan: tulis test bahwa total = `Σ(price × qty) + tax + service_charge - discount` untuk semua kombinasi keranjang
- [x] 19.9 **Property 8** — Penolakan Stok: tulis test bahwa pesanan dengan stok tidak cukup selalu ditolak dan stok tidak berubah
- [x] 19.10 **Property 9** — Pergerakan Stok Konsisten: tulis test bahwa `current_stock` selalu berubah tepat sesuai `quantity_change` dan record `stock_movements` selalu terbuat
- [x] 19.11 **Property 10** — Validasi Voucher: tulis test untuk semua skenario validasi voucher (kedaluwarsa, minimum pembelian, kalkulasi diskon)
- [x] 19.12 **Property 11** — Akumulasi Poin Proporsional: tulis test bahwa poin = `floor(total / rate)` untuk semua nilai transaksi
- [x] 19.13 **Property 12** — Penukaran Poin Round Trip: tulis test bahwa saldo setelah penukaran = saldo sebelumnya - poin ditukar, dan penukaran dengan saldo kurang selalu ditolak
- [x] 19.14 **Property 13** — Rating Idempoten: tulis test bahwa rating kedua pada pesanan yang sama selalu ditolak
- [x] 19.15 **Property 14** — Validasi Rentang Rating: tulis test bahwa hanya nilai integer 1-5 yang diterima

---

### 20. Finalisasi & Deployment

- [x] 20.1 Jalankan semua migration dan seeder di environment staging
- [x] 20.2 Jalankan seluruh test suite (unit, feature, property) dan pastikan semua lulus
- [x] 20.3 Optimasi performa: `php artisan optimize`, `php artisan view:cache`, `php artisan route:cache`
- [x] 20.4 Konfigurasi supervisor untuk queue worker dan Laravel Reverb di production
- [x] 20.5 Setup HTTPS dan konfigurasi CORS untuk API
- [x] 20.6 Load testing endpoint kritis (`POST /api/orders`, `GET /api/menu`) — target < 500ms untuk 95th percentile
- [x] 20.7 Dokumentasi API (Postman collection atau Laravel Scribe)
- [x] 20.8 Review keamanan: CSRF, SQL injection, XSS, validasi webhook signature
