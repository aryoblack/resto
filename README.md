# RestoApp - Sistem Manajemen Restoran

[![Laravel](https://img.shields.io/badge/Laravel-13.x-red)]()
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue)]()

Aplikasi restoran full-stack dengan Customer PWA, Admin Dashboard, dan Kitchen Display System (KDS). Backend memakai Laravel API, Sanctum, Spatie Permission, Reverb/WebSocket, MySQL, dan queue worker.

## Arsitektur

```text
Customer PWA     Admin Dashboard     Kitchen Display
     |                 |                   |
     +-----------------+-------------------+
                       |
                 Laravel API
          Sanctum Auth + RBAC + Reverb
                       |
                  MySQL + Redis
```

## Fitur Utama

### Customer PWA (`/app`)

- Browse menu dengan kategori, pencarian, detail menu, dan varian.
- Keranjang dan checkout dengan voucher, cash, QRIS, dan card.
- Order customer wajib login dengan role `customer`.
- Tracking pesanan dengan polling fallback dan listener realtime channel customer saat Reverb aktif.
- Rating dan review untuk order yang sudah disajikan.
- Loyalty points dan penukaran poin.
- Reservasi meja dengan status pending, confirmed, dan cancelled.
- PWA installable, service worker, offline asset cache, dan Web Push notification.

### Admin Dashboard (`/admin`)

- Dashboard metrik harian.
- CRUD menu, kategori, varian, meja, stok, supplier, staff, promo, reservasi, dan pengaturan.
- Live order board via Laravel Echo/Reverb.
- Laporan penjualan dan stok dengan export Excel/PDF.
- Kasir dan cetak struk via browser atau print bridge lokal.

### Kitchen Display System (`/kds`)

- Board pesanan dapur.
- Timer warna berdasarkan waktu tunggu.
- Checklist item lokal di UI KDS.
- Notifikasi suara saat order baru masuk.
- Update status order melalui API staff.

## Quick Start

### Prasyarat

- PHP 8.2+
- Composer 2.x
- Node.js 20+ dan npm
- MySQL 8.0+
- Redis opsional untuk cache, queue, dan broadcast

### Instalasi

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm run dev
php artisan serve
```

Opsional untuk realtime dan background job:

```bash
php artisan reverb:start
php artisan queue:work
```

### Default Accounts

| Role | Email | Password |
| --- | --- | --- |
| Admin | admin@restoapp.com | Admin@123456 |
| Customer | customer@restoapp.com | Customer@123456 |
| Waiter | waiter@restoapp.com | Waiter@123456 |
| Kasir | kasir@restoapp.com | Kasir@123456 |
| Chef | chef@restoapp.com | Chef@123456 |

Catatan: akun Kasir memakai role `waiter`, sesuai `DemoUserSeeder`.

## API Ringkas

| Group | Method | Endpoint | Auth |
| --- | --- | --- | --- |
| Auth | POST | `/api/auth/register` | Public |
| Auth | POST | `/api/auth/login` | Public |
| Auth | POST | `/api/auth/logout` | Bearer |
| Customer | GET | `/api/customer/menus` | Public |
| Customer | GET | `/api/customer/categories` | Public |
| Customer | GET | `/api/customer/tables` | Public |
| Customer | GET | `/api/customer/orders/{id}?table_id=...` | Public tracking |
| Customer | POST | `/api/customer/orders` | Customer |
| Customer | POST | `/api/customer/orders/{id}/payment/initiate` | Customer |
| Customer | POST | `/api/customer/orders/{id}/rating` | Customer |
| Customer | GET/POST | `/api/customer/loyalty/*` | Customer |
| Customer | GET/POST/DELETE | `/api/customer/reservations` | Customer |
| Staff | GET | `/api/staff/orders` | Waiter/Chef/Admin |
| Staff | PATCH | `/api/staff/orders/{id}/status` | Waiter/Chef/Admin |
| Staff | GET | `/api/staff/kds` | Chef/Admin |
| Admin | CRUD | `/api/admin/menus` | Admin |
| Admin | CRUD | `/api/admin/categories` | Admin |
| Admin | CRUD | `/api/admin/tables` | Admin |
| Admin | CRUD | `/api/admin/inventory` | Admin |
| Admin | CRUD | `/api/admin/promos` | Admin |
| Admin | GET | `/api/admin/reports/*` | Admin |
| Admin | GET/POST | `/api/admin/settings` | Admin |

Postman collection tersedia di:

```text
docs/RestoApp_API.postman_collection.json
```

## Web Push

Web Push memakai `minishlink/web-push`. Pastikan VAPID key di `.env` sudah diisi:

```env
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
```

Customer perlu login, membuka profil, lalu mengaktifkan notifikasi dari tombol di aplikasi.

## Testing

```bash
php artisan test
php artisan test --filter OrderManagerTest
php artisan test --filter PaymentGatewayTest
php artisan test --filter OrderFlowTest
```

Di Windows, dependency Horizon dapat membutuhkan `ext-pcntl` dan `ext-posix` saat operasi Composer. Untuk update dependency lokal Windows, gunakan ignore platform requirement hanya jika paham risikonya.

## Deployment

```bash
bash deploy/deploy.sh
php artisan reverb:start
php artisan queue:work
```

Konfigurasi referensi tersedia di:

- `deploy/nginx.conf`
- `deploy/supervisor.conf`
- `deploy/deploy.sh`

HTTPS wajib untuk PWA, Web Push, dan service worker di production.

## Security

- Auth API memakai Laravel Sanctum.
- RBAC memakai Spatie Permission.
- Customer order dan payment initiate membutuhkan role `customer`.
- Staff/admin endpoint diproteksi role middleware.
- Payment webhook memverifikasi signature Midtrans.
- Account lockout aktif setelah 5 percobaan login gagal.
- Rate limit diterapkan pada create order dan payment initiate.

## Project Structure

```text
app/                    Controllers, Models, Services, Events, Jobs
resources/views/         Admin, Customer, KDS, layouts, emails, reports
routes/api.php           REST API routes
routes/web.php           Web routes
routes/channels.php      Broadcast channel authorization
database/migrations/     Database schema
database/seeders/        Demo data and roles
tests/                   Feature, unit, and property tests
public/manifest.json     PWA manifest
public/sw.js             Service worker
deploy/                  Deployment configs
docs/                    API/security documentation
```

## License

MIT License.
