# RestoApp — Security Review Checklist

## ✅ CSRF Protection
| Area | Status | Notes |
|------|--------|-------|
| Blade forms | ✅ | `@csrf` directive di semua form |
| SPA requests | ✅ | `X-CSRF-TOKEN` header via meta tag |
| API routes | ✅ | Stateless (Sanctum Bearer token), tidak perlu CSRF |
| Payment webhook | ✅ | CSRF-exempt (route di `api.php`) |

## ✅ SQL Injection Prevention
| Area | Status | Notes |
|------|--------|-------|
| Eloquent queries | ✅ | Parameterized via Eloquent ORM |
| Raw queries | ✅ | `DB::raw()` hanya untuk aggregate (HOUR, SUM) — no user input |
| Search/filter | ✅ | `where('name', 'like', "%{$search}%")` via Eloquent binding |

## ✅ XSS Protection
| Area | Status | Notes |
|------|--------|-------|
| Blade output | ✅ | Auto-escaped `{{ }}` (bukan `{!! !!}`) |
| Alpine.js binding | ✅ | `x-text` (auto-escaped), bukan `x-html` |
| CSP Header | ✅ | Content-Security-Policy di Nginx config |
| Input validation | ✅ | Server-side validation di FormRequest classes |

## ✅ Authentication & Authorization
| Area | Status | Notes |
|------|--------|-------|
| Token-based auth | ✅ | Laravel Sanctum (Bearer token) |
| Role-based access | ✅ | Spatie Permission (admin, chef, waiter, customer) |
| Middleware | ✅ | `check.role` middleware pada semua protected routes |
| Account locking | ✅ | Auto-lock setelah 5 failed login attempts (15 min) |
| Password hashing | ✅ | `Hash::make()` (bcrypt) |
| Token revocation | ✅ | Token dihapus saat logout |

## ✅ Webhook Security
| Area | Status | Notes |
|------|--------|-------|
| Midtrans signature | ✅ | Signature key verification di `PaymentController::webhook()` |
| Idempotency | ✅ | Payment status check sebelum update |
| HTTPS required | ✅ | Webhook URL harus HTTPS di production |

## ✅ Data Protection
| Area | Status | Notes |
|------|--------|-------|
| Sensitive fields | ✅ | Password di-hash, tidak pernah di-return di response |
| Hidden attributes | ✅ | `$hidden` pada User model (`password`, `remember_token`) |
| Rate limiting | ✅ | Default throttle middleware pada auth routes |
| File upload | ✅ | Validasi extension & size di MenuController |

## ✅ Infrastructure Security
| Area | Status | Notes |
|------|--------|-------|
| HTTPS | ✅ | Certbot/Let's Encrypt di Nginx config |
| HSTS | ✅ | `Strict-Transport-Security: max-age=31536000` |
| Security headers | ✅ | X-Frame-Options, X-Content-Type-Options, Referrer-Policy |
| CORS | ✅ | Production: env-based allowed origins (bukan wildcard) |
| .env protection | ✅ | Nginx deny access ke dotfiles |
| Debug mode | ⚠️ | Pastikan `APP_DEBUG=false` di production |

## ⚠️ Rekomendasi Tambahan
1. **Rate Limiting API** — Tambahkan throttle:60,1 pada endpoint `POST /api/customer/orders`
2. **2FA (Optional)** — Pertimbangkan 2FA untuk admin accounts
3. **Audit Log** — Log semua aksi admin (create/update/delete) ke tabel audit
4. **Backup** — Setup automated daily backup (DB + storage)
5. **Monitoring** — Setup Laravel Telescope atau Sentry untuk error tracking
