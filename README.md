# RestoApp — Sistem Manajemen Restoran

[![Tests](https://img.shields.io/badge/tests-455%20passed-brightgreen)]()
[![Laravel](https://img.shields.io/badge/Laravel-12.x-red)]()
[![PHP](https://img.shields.io/badge/PHP-8.3-blue)]()

Aplikasi restoran full-stack dengan **Admin Dashboard**, **Customer PWA**, dan **Kitchen Display System (KDS)** yang terintegrasi secara real-time.

---

## 🏗️ Arsitektur

```
┌──────────────────┐   ┌──────────────────┐   ┌──────────────────┐
│  Customer PWA    │   │  Admin Dashboard │   │  Kitchen Display │
│  (Alpine.js)     │   │  (Alpine.js)     │   │  (Alpine.js)     │
└────────┬─────────┘   └────────┬─────────┘   └────────┬─────────┘
         │                      │                       │
         └──────────┬───────────┘───────────────────────┘
                    ▼
         ┌──────────────────┐
         │  Laravel API     │ ◄── Sanctum Auth + RBAC (Spatie)
         │  (REST + WS)     │ ◄── Laravel Reverb (WebSocket)
         └────────┬─────────┘
                  ▼
         ┌──────────────────┐
         │  MySQL + Redis   │
         └──────────────────┘
```

## ✨ Fitur Utama

### Customer PWA (`/app`)
- 🍕 **Browse Menu** — Filter kategori, pencarian, detail + varian
- 🛒 **Keranjang & Checkout** — Tambah/hapus item, voucher, QRIS/Cash
- 📊 **Tracking Pesanan** — Progress bar real-time via WebSocket
- ⭐ **Rating & Review** — Rating 1–5 bintang per pesanan
- 🎁 **Loyalty Points** — Akumulasi & penukaran poin otomatis
- 📅 **Reservasi Meja** — Booking meja dengan pilihan tanggal/waktu
- 📱 **Installable PWA** — Service Worker, offline support, push notifications

### Admin Dashboard (`/admin`)
- 📈 **Dashboard** — Metrik harian (revenue, pesanan, pelanggan, stok kritis)
- 📋 **Manajemen Menu** — CRUD menu, kategori drag-and-drop, varian
- 🪑 **Manajemen Meja** — Grid meja, QR code generator, status real-time
- 📦 **Manajemen Stok** — Bahan baku, auto-deduct, alert stok kritis
- 👥 **Manajemen Karyawan** — CRUD staff, role assignment
- 🔔 **Pesanan Live** — Real-time order board via Laravel Echo
- 🎫 **Promo & Voucher** — Kode diskon, periode, kuota
- 📊 **Laporan & Analitik** — Export Excel/PDF, grafik hourly revenue
- 📅 **Reservasi** — Konfirmasi/tolak booking
- ⚙️ **Pengaturan Sistem** — Pajak, service charge, konversi poin

### Kitchen Display System (`/kds`)
- 🧑‍🍳 **Antrean Kanban** — Kartu pesanan horizontal scroll
- ⏱️ **Timer Cerdas** — Hijau/Kuning/Merah berdasarkan waktu tunggu
- ✅ **Ceklis per Item** — Tap untuk mencoret item selesai
- 🔊 **Notifikasi Suara** — Beep otomatis saat pesanan baru masuk
- 📡 **WebSocket Real-time** — Sinkron dengan Admin & Customer

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.3+
- Composer 2.x
- Node.js 20+ & npm
- MySQL 8.0+
- Redis (optional, untuk caching & queue)

### Installation

```bash
# 1. Clone & install dependencies
git clone https://github.com/your-org/resto.git
cd resto
composer install
npm install

# 2. Environment setup
cp .env.example .env
php artisan key:generate

# 3. Configure database in .env
# DB_DATABASE=resto_app
# DB_USERNAME=root
# DB_PASSWORD=

# 4. Run migrations & seeders
php artisan migrate
php artisan db:seed

# 5. Build frontend
npm run dev    # Development
npm run build  # Production

# 6. Start server
php artisan serve

# 7. (Optional) Start WebSocket server
php artisan reverb:start

# 8. (Optional) Start queue worker
php artisan queue:work
```

### Default Accounts (from seeder)
| Role | Email | Password |
|------|-------|----------|
| Admin | admin@resto.app | password |
| Chef | chef@resto.app | password |
| Waiter | waiter@resto.app | password |
| Customer | customer@resto.app | password |

---

## 🧪 Testing

```bash
# Run all tests (unit + feature + property-based)
php artisan test

# Run specific test groups
php artisan test --filter PropertyBasedTest
php artisan test --filter OrderFlowTest
php artisan test --filter StockManagerTest
```

**Test Coverage:**
- 460+ test methods
- 1580+ assertions
- Property-Based Tests (Eris) — 14 properties, 230+ assertions

---

## 📡 API Documentation

Postman Collection tersedia di:
```
docs/RestoApp_API.postman_collection.json
```

Import ke Postman → Set variable `base_url` → Login → Copy `access_token` ke variable `token`.

### Endpoint Overview

| Group | Method | Endpoint | Auth |
|-------|--------|----------|------|
| **Auth** | POST | `/api/auth/register` | Public |
| | POST | `/api/auth/login` | Public |
| | POST | `/api/auth/logout` | Bearer |
| **Customer** | GET | `/api/customer/menus` | Customer |
| | POST | `/api/customer/orders` | Customer |
| | POST | `/api/customer/orders/{id}/rating` | Customer |
| | GET/POST | `/api/customer/loyalty/*` | Customer |
| | GET/POST | `/api/customer/reservations` | Customer |
| **Staff** | GET | `/api/staff/orders` | Waiter/Chef/Admin |
| | PATCH | `/api/staff/orders/{id}/status` | Waiter/Chef/Admin |
| | GET | `/api/staff/kds` | Chef/Admin |
| **Admin** | CRUD | `/api/admin/menus` | Admin |
| | CRUD | `/api/admin/categories` | Admin |
| | CRUD | `/api/admin/tables` | Admin |
| | CRUD | `/api/admin/inventory` | Admin |
| | CRUD | `/api/admin/promos` | Admin |
| | GET | `/api/admin/reports/*` | Admin |
| | GET/POST | `/api/admin/settings` | Admin |

---

## 🏭 Production Deployment

```bash
# Deploy script
bash deploy/deploy.sh

# Supervisor config (queue + reverb)
sudo cp deploy/supervisor.conf /etc/supervisor/conf.d/resto.conf
sudo supervisorctl reread && sudo supervisorctl update

# Nginx config
sudo cp deploy/nginx.conf /etc/nginx/sites-available/resto.app
sudo ln -s /etc/nginx/sites-available/resto.app /etc/nginx/sites-enabled/
sudo certbot --nginx -d resto.app
sudo systemctl reload nginx
```

### HTTPS (required for PWA)
```bash
sudo certbot --nginx -d resto.app -d www.resto.app
```

---

## 🔒 Security

- ✅ **CSRF Protection** — Sanctum token-based (API), web middleware (forms)
- ✅ **SQL Injection** — Eloquent ORM parameterized queries
- ✅ **XSS** — Blade auto-escaping (`{{ }}`) + Content-Security-Policy header
- ✅ **RBAC** — Spatie Permission: customer, waiter, chef, admin
- ✅ **Rate Limiting** — Laravel throttle middleware on auth routes
- ✅ **Account Locking** — Auto-lock after 5 failed login attempts (15 min)
- ✅ **Webhook Signature** — Midtrans signature verification on payment callback
- ✅ **CORS** — Configured via `config/cors.php`
- ✅ **Security Headers** — X-Frame-Options, X-Content-Type-Options, HSTS (Nginx)

---

## 📁 Project Structure

```
resto/
├── app/
│   ├── Http/Controllers/    # API & Web controllers
│   ├── Models/              # Eloquent models
│   ├── Services/            # Business logic (OrderService, StockService, etc.)
│   ├── Events/              # WebSocket events
│   └── Jobs/                # Queue jobs (ExportReportJob)
├── resources/views/
│   ├── admin/               # Admin Dashboard views (Alpine.js)
│   ├── customer/            # Customer PWA views (Alpine.js SPA)
│   ├── kds/                 # Kitchen Display System views
│   └── layouts/             # Blade layouts (admin, customer, kds)
├── routes/
│   ├── api.php              # REST API routes
│   └── web.php              # Web routes (admin, customer, kds)
├── tests/
│   ├── Feature/             # Feature + Property-Based Tests
│   └── Unit/                # Unit tests
├── deploy/                  # Deployment configs
│   ├── deploy.sh            # Auto-deploy script
│   ├── supervisor.conf      # Queue + Reverb supervisor
│   └── nginx.conf           # Nginx + HTTPS + WSS
├── docs/                    # API documentation
│   └── RestoApp_API.postman_collection.json
└── public/
    ├── manifest.json        # PWA manifest
    └── sw.js                # Service Worker
```

---

## 📜 License

MIT License — Free to use, modify, and distribute.
