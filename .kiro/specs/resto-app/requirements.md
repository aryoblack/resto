# Dokumen Requirements — Aplikasi Restoran (resto-app)

## Pendahuluan

Aplikasi restoran ini adalah sistem terintegrasi berbasis Laravel 13 yang melayani tiga kelompok pengguna utama: **pelanggan** (via mobile/PWA), **staf dapur & pelayan** (via tablet/web), dan **admin/manajer** (via web). Sistem mencakup pemesanan mandiri melalui QR code, manajemen menu dan stok, Kitchen Display System (KDS), laporan penjualan, serta program loyalitas pelanggan.

---

## Glosarium

- **System**: Sistem aplikasi restoran secara keseluruhan
- **Customer_App**: Antarmuka pelanggan berbasis mobile/PWA
- **Admin_Dashboard**: Antarmuka admin/manajer berbasis web/tablet
- **KDS**: Kitchen Display System — tampilan pesanan khusus untuk dapur
- **Order_Manager**: Komponen yang mengelola siklus hidup pesanan
- **Menu_Manager**: Komponen yang mengelola data menu, kategori, dan varian
- **Stock_Manager**: Komponen yang mengelola inventaris bahan baku
- **Auth_System**: Komponen autentikasi dan otorisasi pengguna
- **Payment_Gateway**: Komponen pemrosesan pembayaran
- **Notification_Service**: Komponen pengiriman notifikasi real-time
- **Report_Engine**: Komponen pembuatan laporan dan ekspor data
- **QR_Scanner**: Komponen pemindaian kode QR pada meja
- **Loyalty_Engine**: Komponen pengelolaan poin dan program loyalitas pelanggan
- **Reservation_Manager**: Komponen pengelolaan reservasi meja
- **Promo_Engine**: Komponen pengelolaan promosi dan voucher
- **Pelanggan**: Pengguna dengan role `customer`
- **Pelayan**: Pengguna dengan role `waiter`
- **Koki**: Pengguna dengan role `chef`
- **Admin**: Pengguna dengan role `admin`
- **Dine-in**: Makan di tempat menggunakan meja
- **Delivery**: Pesanan yang diantar ke luar restoran
- **Stok Kritis**: Kondisi ketika `current_stock` ≤ `min_stock` pada tabel `inventory`

---

## Requirements

---

### Requirement 1: Autentikasi dan Otorisasi Pengguna

**User Story:** Sebagai pengguna sistem (pelanggan, pelayan, koki, atau admin), saya ingin dapat masuk ke sistem dengan kredensial yang valid, sehingga saya dapat mengakses fitur sesuai peran saya.

#### Acceptance Criteria

1. WHEN seorang pengguna mengirimkan email dan password yang valid, THE Auth_System SHALL mengautentikasi pengguna dan membuat sesi aktif dalam waktu kurang dari 2 detik.
2. IF seorang pengguna mengirimkan email atau password yang tidak valid, THEN THE Auth_System SHALL menolak akses dan menampilkan pesan kesalahan tanpa mengungkap detail kredensial yang salah.
3. IF seorang pengguna gagal login sebanyak 5 kali berturut-turut, THEN THE Auth_System SHALL mengunci akun selama 15 menit dan menampilkan pesan informasi kepada pengguna.
4. THE Auth_System SHALL menetapkan hak akses berdasarkan role pengguna: `customer`, `waiter`, `chef`, atau `admin`.
5. WHEN seorang pengguna dengan role `customer` mengakses halaman Admin_Dashboard, THE Auth_System SHALL menolak akses dan mengarahkan pengguna ke halaman Customer_App.
6. WHEN seorang pengguna dengan role `chef` atau `waiter` mengakses fitur manajemen karyawan atau laporan keuangan, THE Auth_System SHALL menolak akses dan menampilkan halaman tidak diizinkan.
7. WHEN seorang pengguna memilih logout, THE Auth_System SHALL mengakhiri sesi aktif dan menghapus token autentikasi dalam waktu kurang dari 1 detik.
8. WHERE fitur registrasi pelanggan diaktifkan, THE Auth_System SHALL memvalidasi format email, keunikan email, dan kekuatan password minimal 8 karakter sebelum membuat akun baru.

---

### Requirement 2: Pemindaian QR Code dan Identifikasi Meja

**User Story:** Sebagai pelanggan, saya ingin memindai QR code di meja, sehingga sistem dapat mengenali meja saya dan saya dapat langsung memesan tanpa perlu memanggil pelayan.

#### Acceptance Criteria

1. WHEN seorang pelanggan memindai QR code yang valid pada sebuah meja, THE QR_Scanner SHALL mengidentifikasi nomor meja dan mengarahkan pelanggan ke halaman menu Customer_App dalam waktu kurang dari 3 detik.
2. IF QR code yang dipindai tidak terdaftar dalam sistem, THEN THE QR_Scanner SHALL menampilkan pesan kesalahan dan meminta pelanggan untuk memindai ulang atau menghubungi pelayan.
3. WHILE status meja adalah `occupied`, THE System SHALL menampilkan informasi bahwa meja sedang digunakan dan menawarkan opsi bergabung dengan pesanan yang ada atau menunggu.
4. THE System SHALL menghasilkan QR code unik untuk setiap meja berdasarkan `table_id` dan `table_number`.
5. WHEN seorang admin memperbarui data meja, THE System SHALL meregenerasi QR code untuk meja tersebut dan menonaktifkan QR code lama.

---

### Requirement 3: Manajemen Menu

**User Story:** Sebagai admin, saya ingin mengelola daftar menu beserta kategori dan variannya, sehingga pelanggan selalu melihat informasi menu yang akurat dan terkini.

#### Acceptance Criteria

1. THE Menu_Manager SHALL menampilkan daftar menu dalam bentuk tabel dengan kolom: foto, nama, kategori, harga, stok, dan status (Aktif/Nonaktif).
2. WHEN seorang admin menambahkan menu baru dengan data lengkap (nama, kategori, harga, stok, deskripsi, dan gambar), THE Menu_Manager SHALL menyimpan data menu dan menampilkan menu baru dalam daftar dalam waktu kurang dari 3 detik.
3. IF seorang admin mengirimkan data menu tanpa mengisi field wajib (nama, harga, atau kategori), THEN THE Menu_Manager SHALL menolak penyimpanan dan menampilkan pesan validasi untuk setiap field yang kosong.
4. WHEN seorang admin mengubah status menu menjadi `Nonaktif`, THE Menu_Manager SHALL menyembunyikan menu tersebut dari tampilan Customer_App dalam waktu kurang dari 5 detik.
5. WHEN seorang admin menghapus menu, THE Menu_Manager SHALL meminta konfirmasi sebelum menghapus dan menyimpan riwayat penghapusan untuk keperluan audit.
6. THE Menu_Manager SHALL mendukung pengelolaan kategori menu dengan atribut nama dan urutan tampil (`sort_order`).
7. WHEN seorang admin mengubah urutan kategori, THE Menu_Manager SHALL memperbarui urutan tampil kategori di Customer_App dalam waktu kurang dari 5 detik.
8. THE Menu_Manager SHALL mendukung penambahan varian menu (contoh: level pedas, topping) dengan atribut nama varian dan harga tambahan (`extra_price`).
9. WHERE fitur upload gambar diaktifkan, THE Menu_Manager SHALL menerima file gambar berformat JPG, PNG, atau WebP dengan ukuran maksimal 2 MB dan mengoptimalkan gambar sebelum disimpan.

---

### Requirement 4: Tampilan Menu untuk Pelanggan

**User Story:** Sebagai pelanggan, saya ingin melihat menu restoran dengan tampilan yang menarik dan dapat difilter berdasarkan kategori, sehingga saya dapat menemukan dan memilih makanan dengan mudah.

#### Acceptance Criteria

1. THE Customer_App SHALL menampilkan menu dalam bentuk grid dengan informasi: foto, nama, harga, rating, dan tombol tambah ke keranjang.
2. WHEN seorang pelanggan memilih filter kategori (Semua, Makanan, Minuman, Snack, atau kategori lainnya), THE Customer_App SHALL memfilter dan menampilkan hanya menu dari kategori yang dipilih dalam waktu kurang dari 1 detik.
3. WHEN seorang pelanggan mengetikkan kata kunci pada kolom pencarian, THE Customer_App SHALL menampilkan hasil pencarian menu yang relevan dalam waktu kurang dari 1 detik.
4. WHILE stok sebuah menu adalah 0 atau status menu adalah `Nonaktif`, THE Customer_App SHALL menampilkan menu tersebut dengan label "Habis" dan menonaktifkan tombol tambah ke keranjang.
5. THE Customer_App SHALL menampilkan banner promosi aktif di bagian atas halaman menu.
6. WHEN seorang pelanggan memilih sebuah menu yang memiliki varian, THE Customer_App SHALL menampilkan pilihan varian beserta harga tambahannya sebelum menambahkan ke keranjang.

---

### Requirement 5: Keranjang Belanja dan Pemesanan

**User Story:** Sebagai pelanggan, saya ingin mengelola keranjang belanja dan melakukan pemesanan, sehingga saya dapat memesan makanan sesuai keinginan dengan mudah.

#### Acceptance Criteria

1. WHEN seorang pelanggan menambahkan menu ke keranjang, THE Customer_App SHALL memperbarui jumlah item di ikon keranjang secara langsung.
2. THE Customer_App SHALL memungkinkan pelanggan mengubah jumlah item atau menghapus item dari keranjang sebelum melakukan pemesanan.
3. THE Customer_App SHALL menghitung dan menampilkan subtotal, diskon (jika ada voucher), dan total harga secara akurat di halaman keranjang.
4. WHEN seorang pelanggan menambahkan catatan khusus pada item pesanan, THE Customer_App SHALL menyimpan catatan tersebut dan meneruskannya ke dapur melalui Order_Manager.
5. WHEN seorang pelanggan mengonfirmasi pesanan, THE Order_Manager SHALL membuat record pesanan baru dengan status `Diterima` dan menyimpan harga menu pada saat pemesanan (`price_at_time`) dalam waktu kurang dari 3 detik.
6. IF seorang pelanggan mengonfirmasi pesanan saat stok salah satu item tidak mencukupi, THEN THE Order_Manager SHALL menolak pesanan, menginformasikan item yang tidak tersedia, dan meminta pelanggan memperbarui keranjang.
7. WHEN pesanan berhasil dibuat, THE Notification_Service SHALL mengirimkan notifikasi ke Admin_Dashboard dan KDS secara real-time dalam waktu kurang dari 5 detik.

---

### Requirement 6: Pembayaran

**User Story:** Sebagai pelanggan, saya ingin membayar pesanan dengan berbagai metode pembayaran, sehingga transaksi dapat diselesaikan dengan nyaman.

#### Acceptance Criteria

1. THE Payment_Gateway SHALL mendukung metode pembayaran: tunai, kartu debit/kredit, dan dompet digital (QRIS).
2. WHEN seorang pelanggan memilih metode pembayaran QRIS, THE Payment_Gateway SHALL menghasilkan kode QRIS dinamis yang unik untuk setiap transaksi.
3. WHEN pembayaran berhasil dikonfirmasi oleh Payment_Gateway, THE Order_Manager SHALL memperbarui `payment_status` menjadi `paid` dan `order_status` menjadi `Diproses` dalam waktu kurang dari 5 detik.
4. IF pembayaran gagal atau kedaluwarsa, THEN THE Payment_Gateway SHALL memperbarui `payment_status` menjadi `failed` dan menginformasikan pelanggan untuk mencoba kembali.
5. WHEN pembayaran tunai dipilih, THE System SHALL menampilkan total tagihan kepada pelayan untuk diproses secara manual dan memperbarui status setelah pelayan mengonfirmasi penerimaan pembayaran.
6. THE System SHALL menyimpan riwayat setiap transaksi pembayaran termasuk metode, jumlah, waktu, dan status untuk keperluan laporan.

---

### Requirement 7: Manajemen Pesanan dan Dapur (KDS)

**User Story:** Sebagai pelayan dan koki, saya ingin melihat dan memperbarui status pesanan secara real-time, sehingga alur kerja dapur dan pelayanan berjalan efisien.

#### Acceptance Criteria

1. THE Admin_Dashboard SHALL menampilkan semua pesanan aktif secara real-time pada halaman Pesanan Live dengan informasi: nomor meja/jenis pesanan, waktu pesan, total tagihan, dan status saat ini.
2. WHEN seorang pelayan atau admin mengubah status pesanan, THE Order_Manager SHALL memperbarui status pesanan dan menyebarkan perubahan ke semua tampilan terkait (Admin_Dashboard, KDS, Customer_App) dalam waktu kurang dari 3 detik.
3. THE Order_Manager SHALL mendukung alur status pesanan secara berurutan: `Diterima` → `Dimasak` → `Selesai` → `Disajikan`.
4. THE KDS SHALL menampilkan hanya pesanan dengan status `Dimasak` beserta detail item, catatan khusus, dan waktu pesanan masuk.
5. WHEN status pesanan berubah menjadi `Selesai`, THE Notification_Service SHALL mengirimkan notifikasi kepada pelayan yang bertugas.
6. THE Admin_Dashboard SHALL menyediakan filter pesanan berdasarkan: jenis (dine-in/delivery), status, dan nomor meja.
7. WHEN seorang admin menerima pesanan baru, THE Admin_Dashboard SHALL menampilkan notifikasi visual dan suara pada halaman Pesanan Live.

---

### Requirement 8: Pelacakan Pesanan oleh Pelanggan

**User Story:** Sebagai pelanggan, saya ingin melacak status pesanan saya secara real-time, sehingga saya mengetahui kapan makanan saya akan siap.

#### Acceptance Criteria

1. WHEN seorang pelanggan membuka halaman status pesanan, THE Customer_App SHALL menampilkan status pesanan terkini secara real-time tanpa perlu me-refresh halaman.
2. THE Customer_App SHALL menampilkan progres pesanan secara visual menggunakan indikator tahapan: Diterima → Dimasak → Selesai → Disajikan.
3. WHEN status pesanan berubah, THE Notification_Service SHALL mengirimkan notifikasi push kepada pelanggan dalam waktu kurang dari 5 detik.

---

### Requirement 9: Manajemen Stok dan Inventaris

**User Story:** Sebagai admin, saya ingin mengelola stok bahan baku dan mendapatkan peringatan otomatis saat stok menipis, sehingga operasional dapur tidak terganggu karena kehabisan bahan.

#### Acceptance Criteria

1. THE Stock_Manager SHALL menampilkan daftar bahan baku dalam tabel dengan kolom: nama bahan, satuan, stok saat ini, stok minimal, dan supplier.
2. WHEN seorang admin mencatat pemasukan stok baru, THE Stock_Manager SHALL menambahkan jumlah ke `current_stock` dan membuat record di tabel `stock_movements` dengan tipe `in`.
3. WHEN sebuah pesanan berhasil dibuat, THE Stock_Manager SHALL mengurangi `current_stock` bahan baku yang terkait secara otomatis dan membuat record di `stock_movements` dengan tipe `out`.
4. WHILE `current_stock` sebuah bahan baku kurang dari atau sama dengan `min_stock`, THE Notification_Service SHALL menampilkan peringatan stok kritis pada Admin_Dashboard secara persisten hingga stok diisi ulang.
5. WHILE `current_stock` sebuah bahan baku kurang dari atau sama dengan `min_stock`, THE Stock_Manager SHALL menampilkan indikator visual peringatan pada baris bahan baku tersebut di tabel inventaris.
6. THE Stock_Manager SHALL menyimpan seluruh riwayat pergerakan stok (`stock_movements`) dengan informasi: bahan baku, jumlah perubahan, tipe (in/out), catatan, dan waktu kejadian.
7. IF seorang admin mengirimkan data stok dengan nilai negatif atau bukan angka, THEN THE Stock_Manager SHALL menolak input dan menampilkan pesan validasi.

---

### Requirement 10: Manajemen Meja

**User Story:** Sebagai admin, saya ingin mengelola data meja restoran, sehingga status ketersediaan meja selalu akurat dan QR code dapat dikelola dengan mudah.

#### Acceptance Criteria

1. THE System SHALL menampilkan daftar semua meja dengan informasi: nomor meja, status (available/occupied), dan QR code.
2. WHEN seorang admin menambahkan meja baru, THE System SHALL membuat record meja, menghasilkan QR code unik, dan menampilkan meja baru dalam daftar.
3. WHEN semua pesanan pada sebuah meja telah berstatus `Disajikan` dan pembayaran selesai, THE Order_Manager SHALL memperbarui status meja menjadi `available` secara otomatis.
4. WHEN seorang pelanggan berhasil memindai QR code meja, THE System SHALL memperbarui status meja menjadi `occupied`.
5. THE System SHALL memungkinkan admin mengubah status meja secara manual untuk keperluan operasional (contoh: meja dalam perbaikan).

---

### Requirement 11: Manajemen Karyawan

**User Story:** Sebagai admin, saya ingin mengelola data karyawan dan hak akses mereka, sehingga setiap karyawan hanya dapat mengakses fitur yang sesuai dengan perannya.

#### Acceptance Criteria

1. THE Admin_Dashboard SHALL menampilkan daftar karyawan dengan informasi: nama, email, role, dan status akun.
2. WHEN seorang admin menambahkan karyawan baru, THE Auth_System SHALL membuat akun dengan role yang ditentukan (waiter atau chef) dan mengirimkan kredensial awal ke email karyawan.
3. WHEN seorang admin mengubah role karyawan, THE Auth_System SHALL memperbarui hak akses karyawan tersebut secara langsung pada sesi berikutnya.
4. WHEN seorang admin menonaktifkan akun karyawan, THE Auth_System SHALL mencabut akses karyawan tersebut dan mengakhiri semua sesi aktif milik karyawan tersebut.
5. IF seorang admin mencoba menghapus akun karyawan yang memiliki riwayat pesanan aktif, THEN THE System SHALL menolak penghapusan dan menyarankan admin untuk menonaktifkan akun.

---

### Requirement 12: Promosi dan Voucher

**User Story:** Sebagai admin, saya ingin membuat dan mengelola promosi serta voucher diskon, sehingga dapat meningkatkan penjualan dan loyalitas pelanggan.

#### Acceptance Criteria

1. THE Promo_Engine SHALL mendukung pembuatan promosi dengan tipe: diskon persentase, diskon nominal, dan minimum pembelian.
2. WHEN seorang admin membuat promosi baru, THE Promo_Engine SHALL menyimpan data promosi dengan atribut: nama, tipe diskon, nilai diskon, minimum pembelian, tanggal mulai, tanggal berakhir, dan status aktif.
3. WHEN seorang pelanggan memasukkan kode voucher yang valid pada halaman keranjang, THE Promo_Engine SHALL menghitung dan menerapkan diskon sesuai ketentuan voucher.
4. IF seorang pelanggan memasukkan kode voucher yang sudah kedaluwarsa atau tidak valid, THEN THE Promo_Engine SHALL menolak voucher dan menampilkan pesan kesalahan yang informatif.
5. IF seorang pelanggan memasukkan kode voucher yang valid namun total belanja di bawah minimum pembelian, THEN THE Promo_Engine SHALL menolak voucher dan menginformasikan jumlah minimum pembelian yang diperlukan.
6. WHILE sebuah promosi aktif, THE Customer_App SHALL menampilkan banner promosi tersebut di halaman beranda.
7. THE Promo_Engine SHALL mencatat setiap penggunaan voucher beserta data pelanggan dan pesanan terkait untuk keperluan audit.

---

### Requirement 13: Program Loyalitas Pelanggan (Poin)

**User Story:** Sebagai pelanggan, saya ingin mendapatkan poin dari setiap transaksi, sehingga saya termotivasi untuk terus memesan di restoran ini.

#### Acceptance Criteria

1. WHEN pembayaran pesanan seorang pelanggan berhasil dikonfirmasi, THE Loyalty_Engine SHALL menambahkan poin kepada akun pelanggan berdasarkan total nilai transaksi.
2. THE Customer_App SHALL menampilkan saldo poin terkini milik pelanggan pada halaman profil.
3. WHEN seorang pelanggan memilih untuk menukarkan poin sebagai diskon pada saat checkout, THE Loyalty_Engine SHALL menghitung nilai diskon dari poin yang ditukarkan dan mengurangi saldo poin pelanggan.
4. IF saldo poin pelanggan tidak mencukupi untuk penukaran yang diminta, THEN THE Loyalty_Engine SHALL menolak penukaran dan menampilkan saldo poin yang tersedia.
5. THE Loyalty_Engine SHALL menyimpan riwayat perolehan dan penukaran poin setiap pelanggan untuk keperluan transparansi dan audit.

---

### Requirement 14: Reservasi Meja

**User Story:** Sebagai pelanggan, saya ingin melakukan reservasi meja terlebih dahulu, sehingga saya dapat memastikan ketersediaan tempat sebelum datang ke restoran.

#### Acceptance Criteria

1. WHEN seorang pelanggan mengajukan reservasi dengan data lengkap (tanggal, waktu, jumlah orang, dan nomor meja), THE Reservation_Manager SHALL menyimpan data reservasi dengan status `pending` dan mengirimkan konfirmasi kepada pelanggan.
2. IF seorang pelanggan mengajukan reservasi untuk meja yang sudah dipesan pada tanggal dan waktu yang sama, THEN THE Reservation_Manager SHALL menolak reservasi dan menyarankan meja atau waktu alternatif yang tersedia.
3. WHEN seorang admin mengonfirmasi reservasi, THE Reservation_Manager SHALL memperbarui status reservasi menjadi `confirmed` dan mengirimkan notifikasi konfirmasi kepada pelanggan.
4. WHEN seorang admin membatalkan reservasi, THE Reservation_Manager SHALL memperbarui status menjadi `cancelled` dan mengirimkan notifikasi pembatalan kepada pelanggan.
5. THE Reservation_Manager SHALL menampilkan daftar semua reservasi pada Admin_Dashboard dengan filter berdasarkan tanggal dan status.

---

### Requirement 15: Laporan dan Analitik

**User Story:** Sebagai admin/manajer, saya ingin melihat laporan penjualan dan stok yang komprehensif, sehingga saya dapat membuat keputusan bisnis berdasarkan data yang akurat.

#### Acceptance Criteria

1. THE Report_Engine SHALL menyediakan laporan penjualan dengan filter periode: harian, mingguan, dan bulanan.
2. THE Report_Engine SHALL menampilkan metrik utama pada laporan penjualan: total omzet, jumlah pesanan, rata-rata nilai pesanan, menu terlaris, dan rincian per metode pembayaran.
3. THE Report_Engine SHALL menyediakan laporan stok opname yang menampilkan kondisi stok bahan baku saat ini dibandingkan dengan stok minimal.
4. WHEN seorang admin meminta ekspor laporan, THE Report_Engine SHALL menghasilkan file dalam format Excel (.xlsx) atau PDF sesuai pilihan admin dalam waktu kurang dari 30 detik.
5. THE Admin_Dashboard SHALL menampilkan grafik pendapatan per jam pada halaman utama Dashboard untuk membantu identifikasi jam sibuk.
6. THE Admin_Dashboard SHALL menampilkan kartu metrik ringkasan pada halaman utama: total pendapatan hari ini, jumlah pesanan hari ini, jumlah pelanggan hari ini, dan jumlah bahan baku dalam kondisi stok kritis.

---

### Requirement 16: Rating dan Ulasan

**User Story:** Sebagai pelanggan, saya ingin memberikan rating dan ulasan setelah pesanan selesai, sehingga restoran dapat meningkatkan kualitas layanan berdasarkan masukan pelanggan.

#### Acceptance Criteria

1. WHEN status pesanan seorang pelanggan berubah menjadi `Disajikan`, THE Customer_App SHALL menampilkan prompt kepada pelanggan untuk memberikan rating dan ulasan.
2. THE Customer_App SHALL menerima rating dalam skala 1 hingga 5 bintang dan ulasan teks opsional untuk setiap pesanan.
3. IF seorang pelanggan mencoba memberikan rating untuk pesanan yang sudah pernah diberi rating, THEN THE System SHALL menolak pengiriman dan menampilkan rating yang sudah ada.
4. THE Admin_Dashboard SHALL menampilkan ringkasan rating rata-rata dan daftar ulasan terbaru pada halaman laporan.

---

### Requirement 17: Notifikasi Real-time

**User Story:** Sebagai pengguna sistem, saya ingin menerima notifikasi real-time untuk kejadian penting, sehingga saya dapat merespons dengan cepat tanpa perlu terus-menerus memeriksa aplikasi.

#### Acceptance Criteria

1. WHEN pesanan baru masuk, THE Notification_Service SHALL mengirimkan notifikasi real-time kepada Admin_Dashboard dan KDS dalam waktu kurang dari 5 detik.
2. WHEN status pesanan berubah, THE Notification_Service SHALL mengirimkan notifikasi kepada pelanggan terkait dalam waktu kurang dari 5 detik.
3. WHEN `current_stock` sebuah bahan baku turun ke kondisi Stok Kritis, THE Notification_Service SHALL mengirimkan notifikasi kepada Admin_Dashboard.
4. THE Notification_Service SHALL mendukung pengiriman notifikasi melalui: in-app notification (web socket/SSE) untuk Admin_Dashboard dan KDS, serta push notification untuk Customer_App.
5. IF koneksi real-time terputus, THEN THE Notification_Service SHALL mencoba menyambung kembali secara otomatis dan menampilkan indikator status koneksi kepada pengguna.

---

### Requirement 18: Pengaturan Sistem

**User Story:** Sebagai admin, saya ingin mengonfigurasi pengaturan umum sistem, sehingga aplikasi dapat disesuaikan dengan kebutuhan operasional restoran.

#### Acceptance Criteria

1. THE Admin_Dashboard SHALL menyediakan halaman Pengaturan yang memungkinkan admin mengonfigurasi: nama restoran, logo, jam operasional, dan informasi kontak.
2. WHEN seorang admin menyimpan perubahan pengaturan, THE System SHALL menerapkan perubahan tersebut pada seluruh tampilan aplikasi dalam waktu kurang dari 10 detik.
3. THE Admin_Dashboard SHALL memungkinkan admin mengonfigurasi nilai konversi poin (contoh: 1 poin = Rp 100 diskon) pada halaman Pengaturan.
4. THE Admin_Dashboard SHALL memungkinkan admin mengonfigurasi persentase pajak dan biaya layanan yang akan diterapkan pada setiap transaksi.
