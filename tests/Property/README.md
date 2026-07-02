# Property-Based Tests

Direktori ini berisi property-based tests menggunakan library Eris.

Setiap test harus diberi tag komentar yang mereferensikan property di design document.

Contoh:

```php
/**
 * Feature: resto-app, Property 7: Kalkulasi Total Pesanan Akurat
 * Validates: Requirements 5.3, 5.5
 */
```

Konfigurasi minimum: setiap property test dijalankan minimal 100 iterasi.
