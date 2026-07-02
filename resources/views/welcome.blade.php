<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#FF7A2F">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>RestoApp - Sistem Pemesanan Restoran Modern</title>
    <meta name="description" content="RestoApp membantu restoran mengelola pemesanan QR meja, reservasi, KDS dapur, stok, promo, loyalitas pelanggan, dan laporan operasional dalam satu platform.">
    <meta name="keywords" content="aplikasi restoran, sistem restoran, QR order, POS restoran, reservasi meja, kitchen display system, manajemen menu restoran">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url('/') }}">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:title" content="RestoApp - Sistem Pemesanan Restoran Modern">
    <meta property="og:description" content="Platform restoran untuk pemesanan QR, reservasi meja, dapur, stok, promo, loyalitas, dan laporan.">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:site_name" content="RestoApp">
    <meta property="og:image" content="{{ asset('icons/icon-512x512.png') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="RestoApp - Sistem Pemesanan Restoran Modern">
    <meta name="twitter:description" content="Kelola pemesanan QR, reservasi, dapur, stok, promo, loyalitas, dan laporan dalam satu aplikasi.">
    <meta name="twitter:image" content="{{ asset('icons/icon-512x512.png') }}">

    <link rel="icon" href="{{ asset('icons/icon-192x192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192x192.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://images.unsplash.com">
    <link href="https://fonts.bunny.net/css?family=poppins:600,700,800,900|inter:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @php
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'RestoApp',
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'description' => 'Sistem restoran modern untuk pemesanan QR meja, reservasi, KDS dapur, stok, promo, loyalitas pelanggan, dan laporan operasional.',
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'IDR',
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
</head>
<body class="bg-white font-sans text-gray-950 antialiased">
    <div class="min-h-screen overflow-hidden">
        <header class="fixed inset-x-0 top-0 z-50 border-b border-white/10 bg-gray-950/55 backdrop-blur-xl">
            <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8" aria-label="Navigasi utama">
                <a href="{{ route('landing') }}" class="flex items-center gap-3" aria-label="RestoApp">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-500 text-white shadow-lg shadow-primary-900/20">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                    </span>
                    <span class="font-heading text-xl font-black tracking-tight text-white">Resto<span class="text-primary-300">App</span></span>
                </a>

                <div class="hidden items-center gap-7 text-sm font-bold text-white/75 md:flex">
                    <a href="#fitur" class="transition hover:text-white">Fitur</a>
                    <a href="#alur" class="transition hover:text-white">Alur</a>
                    <a href="#operasional" class="transition hover:text-white">Operasional</a>
                    <a href="#faq" class="transition hover:text-white">FAQ</a>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.login') }}" class="hidden rounded-full px-4 py-2 text-sm font-extrabold text-white/80 transition hover:bg-white/10 hover:text-white sm:inline-flex">Staff Login</a>
                    <a href="{{ route('customer.app') }}" class="rounded-full bg-primary-500 px-4 py-2 text-sm font-extrabold text-white shadow-lg shadow-primary-950/20 transition hover:bg-primary-600">Buka App</a>
                </div>
            </nav>
        </header>

        <main>
            <section class="relative flex min-h-[92vh] items-end overflow-hidden bg-gray-950 px-4 pb-12 pt-32 sm:px-6 lg:px-8">
                <img
                    src="https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=2200&q=85"
                    alt="Tim restoran melayani pelanggan dengan sistem pemesanan modern"
                    class="absolute inset-0 h-full w-full object-cover opacity-70"
                    fetchpriority="high"
                >
                <div class="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-950/55 to-gray-950/25"></div>
                <div class="absolute inset-x-0 bottom-0 h-28 bg-gradient-to-t from-white to-transparent"></div>

                <div class="relative z-10 mx-auto grid w-full max-w-7xl items-end gap-10 lg:grid-cols-[minmax(0,1.05fr)_minmax(340px,0.6fr)]">
                    <div class="max-w-4xl pb-6 text-white">
                        <p class="mb-5 inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-black uppercase tracking-[0.22em] text-primary-100 backdrop-blur-md">QR Order, KDS, Reservasi, Loyalty</p>
                        <h1 class="font-heading text-5xl font-black leading-[0.95] tracking-tight sm:text-6xl lg:text-7xl">Operasional restoran yang terasa cepat, rapi, dan siap tumbuh.</h1>
                        <p class="mt-6 max-w-2xl text-base leading-8 text-white/82 sm:text-lg">RestoApp menyatukan pemesanan dari meja, reservasi pelanggan, antrean dapur, stok bahan, promo, poin reward, dan laporan harian dalam satu pengalaman yang mudah dipakai tim restoran.</p>
                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ route('customer.app') }}" class="inline-flex items-center justify-center rounded-full bg-primary-500 px-7 py-4 text-sm font-black text-white shadow-2xl shadow-primary-950/30 transition hover:-translate-y-0.5 hover:bg-primary-600">Coba Customer App</a>
                            <a href="{{ route('admin.login') }}" class="inline-flex items-center justify-center rounded-full border border-white/25 bg-white/10 px-7 py-4 text-sm font-black text-white backdrop-blur-md transition hover:bg-white/20">Masuk Dashboard Staff</a>
                        </div>
                    </div>

                    <div class="rounded-[1.75rem] border border-white/15 bg-white/12 p-4 text-white shadow-2xl shadow-gray-950/30 backdrop-blur-xl">
                        <div class="rounded-[1.25rem] bg-white p-4 text-gray-950 shadow-xl">
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-widest text-primary-600">Live Dining</p>
                                    <h2 class="font-heading text-xl font-black">Meja 12</h2>
                                </div>
                                <span class="rounded-full bg-green-50 px-3 py-1 text-xs font-black text-green-700">Aktif</span>
                            </div>
                            <div class="space-y-3">
                                <div class="rounded-2xl bg-gray-50 p-4">
                                    <div class="flex items-center justify-between">
                                        <span class="font-black">Nasi Goreng Rempah</span>
                                        <span class="text-sm font-black text-primary-600">2x</span>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500">Catatan: pedas sedang</p>
                                </div>
                                <div class="rounded-2xl bg-gray-50 p-4">
                                    <div class="flex items-center justify-between">
                                        <span class="font-black">Es Kopi Aren</span>
                                        <span class="text-sm font-black text-primary-600">1x</span>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500">Status: dikirim ke dapur</p>
                                </div>
                            </div>
                            <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                                <div class="rounded-2xl bg-primary-50 p-3">
                                    <p class="text-2xl font-black text-primary-600">7</p>
                                    <p class="text-[11px] font-bold text-gray-500">Pesanan</p>
                                </div>
                                <div class="rounded-2xl bg-cyan-50 p-3">
                                    <p class="text-2xl font-black text-cyan-700">4</p>
                                    <p class="text-[11px] font-bold text-gray-500">Dapur</p>
                                </div>
                                <div class="rounded-2xl bg-yellow-50 p-3">
                                    <p class="text-2xl font-black text-yellow-700">12</p>
                                    <p class="text-[11px] font-bold text-gray-500">Meja</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="fitur" class="px-4 py-20 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <div class="max-w-3xl">
                        <p class="text-sm font-black uppercase tracking-[0.22em] text-primary-600">Satu Sistem</p>
                        <h2 class="mt-3 font-heading text-3xl font-black tracking-tight text-gray-950 sm:text-5xl">Dibuat untuk ritme restoran yang sibuk.</h2>
                        <p class="mt-4 text-lg leading-8 text-gray-600">Setiap modul saling terhubung, jadi pelanggan, waiter, dapur, kasir, dan owner melihat data yang sama tanpa kerja manual berulang.</p>
                    </div>

                    <div class="mt-10 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        @foreach ([
                            ['QR Meja', 'Pelanggan scan QR, pilih menu, bayar, dan status pesanan langsung masuk ke operasional.'],
                            ['Reservasi Cerdas', 'Meja yang sudah dipesan pada tanggal dan jam tertentu bisa dikunci agar tidak bentrok.'],
                            ['KDS Dapur', 'Chef melihat antrean masakan real-time agar proses produksi lebih jelas.'],
                            ['Stok & Laporan', 'Pantau bahan, promo, poin reward, serta laporan penjualan dalam dashboard admin.'],
                        ] as $feature)
                            <article class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                                <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-950 text-primary-300">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <h3 class="font-heading text-xl font-black text-gray-950">{{ $feature[0] }}</h3>
                                <p class="mt-3 text-sm leading-7 text-gray-600">{{ $feature[1] }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="alur" class="bg-gray-950 px-4 py-20 text-white sm:px-6 lg:px-8">
                <div class="mx-auto grid max-w-7xl gap-12 lg:grid-cols-[0.8fr_1.2fr] lg:items-center">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.22em] text-primary-300">Alur Layanan</p>
                        <h2 class="mt-3 font-heading text-3xl font-black tracking-tight sm:text-5xl">Dari meja ke dapur tanpa kehilangan konteks.</h2>
                        <p class="mt-5 text-lg leading-8 text-white/70">RestoApp menjaga alur tetap sederhana: pelanggan memesan, tim memproses, dapur mengeksekusi, admin memantau performa.</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ([
                            ['01', 'Scan QR', 'Setiap meja punya QR unik yang membawa customer langsung ke sesi meja.'],
                            ['02', 'Pilih menu', 'Menu, kategori, promo, catatan, dan keranjang dioptimalkan untuk layar mobile.'],
                            ['03', 'Dapur proses', 'Pesanan masuk ke dashboard staff dan KDS untuk diproses sesuai status.'],
                            ['04', 'Pantau bisnis', 'Owner melihat laporan, stok, reservasi, rating, dan transaksi dari admin panel.'],
                        ] as $step)
                            <article class="rounded-2xl border border-white/10 bg-white/5 p-6">
                                <p class="text-sm font-black text-primary-300">{{ $step[0] }}</p>
                                <h3 class="mt-4 font-heading text-2xl font-black">{{ $step[1] }}</h3>
                                <p class="mt-3 text-sm leading-7 text-white/65">{{ $step[2] }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="operasional" class="px-4 py-20 sm:px-6 lg:px-8">
                <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-2 lg:items-center">
                    <div class="overflow-hidden rounded-[2rem] border border-gray-100 bg-gray-50 shadow-xl">
                        <div class="border-b border-gray-200 bg-white px-5 py-4">
                            <div class="flex items-center gap-2">
                                <span class="h-3 w-3 rounded-full bg-red-400"></span>
                                <span class="h-3 w-3 rounded-full bg-yellow-400"></span>
                                <span class="h-3 w-3 rounded-full bg-green-400"></span>
                            </div>
                        </div>
                        <div class="grid gap-4 p-5 sm:grid-cols-2">
                            <div class="rounded-2xl bg-white p-5 shadow-sm">
                                <p class="text-sm font-bold text-gray-500">Pendapatan Hari Ini</p>
                                <p class="mt-3 text-3xl font-black text-gray-950">Rp 8,4 jt</p>
                                <p class="mt-2 text-sm font-bold text-green-600">+18% dari kemarin</p>
                            </div>
                            <div class="rounded-2xl bg-white p-5 shadow-sm">
                                <p class="text-sm font-bold text-gray-500">Reservasi Menunggu</p>
                                <p class="mt-3 text-3xl font-black text-gray-950">6</p>
                                <p class="mt-2 text-sm font-bold text-primary-600">Perlu konfirmasi</p>
                            </div>
                            <div class="rounded-2xl bg-white p-5 shadow-sm sm:col-span-2">
                                <p class="text-sm font-bold text-gray-500">Antrean Dapur</p>
                                <div class="mt-4 space-y-3">
                                    <div class="flex items-center justify-between rounded-xl bg-gray-50 p-3">
                                        <span class="font-black">#1042 - Meja 5</span>
                                        <span class="rounded-full bg-yellow-100 px-3 py-1 text-xs font-black text-yellow-800">Dimasak</span>
                                    </div>
                                    <div class="flex items-center justify-between rounded-xl bg-gray-50 p-3">
                                        <span class="font-black">#1043 - Meja 9</span>
                                        <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-blue-800">Diproses</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.22em] text-primary-600">Scalable</p>
                        <h2 class="mt-3 font-heading text-3xl font-black tracking-tight text-gray-950 sm:text-5xl">Siap untuk cafe kecil sampai restoran multi-role.</h2>
                        <p class="mt-5 text-lg leading-8 text-gray-600">Struktur role admin, waiter, chef, dan customer membuat sistem mudah diperluas. Modul dapat ditambah tanpa mengubah pengalaman dasar pengguna.</p>
                        <dl class="mt-8 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl bg-primary-50 p-5">
                                <dt class="font-black text-primary-700">Role jelas</dt>
                                <dd class="mt-2 text-sm leading-6 text-gray-600">Hak akses staff dipisahkan sesuai pekerjaan.</dd>
                            </div>
                            <div class="rounded-2xl bg-cyan-50 p-5">
                                <dt class="font-black text-cyan-800">API-first</dt>
                                <dd class="mt-2 text-sm leading-6 text-gray-600">Customer app dan admin panel berbagi data yang sama.</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </section>

            <section id="faq" class="bg-gray-50 px-4 py-20 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-5xl">
                    <div class="text-center">
                        <p class="text-sm font-black uppercase tracking-[0.22em] text-primary-600">FAQ</p>
                        <h2 class="mt-3 font-heading text-3xl font-black tracking-tight text-gray-950 sm:text-5xl">Pertanyaan yang sering muncul.</h2>
                    </div>
                    <div class="mt-10 grid gap-4 md:grid-cols-2">
                        @foreach ([
                            ['Apakah customer harus install aplikasi?', 'Tidak. Customer cukup membuka web app atau scan QR meja dari browser.'],
                            ['Apakah bisa dipakai untuk reservasi?', 'Bisa. Customer dapat membuat reservasi dan admin mengonfirmasi dari dashboard.'],
                            ['Apakah ada tampilan dapur?', 'Ada. KDS membantu chef melihat antrean dan status pesanan.'],
                            ['Apakah cocok untuk operasional harian?', 'Ya. Menu, kategori, meja, stok, promo, staff, reservasi, dan laporan sudah tersedia.'],
                        ] as $faq)
                            <article class="rounded-2xl border border-gray-100 bg-white p-6">
                                <h3 class="font-heading text-lg font-black text-gray-950">{{ $faq[0] }}</h3>
                                <p class="mt-3 text-sm leading-7 text-gray-600">{{ $faq[1] }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="px-4 py-20 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl rounded-[2rem] bg-gray-950 p-8 text-white shadow-2xl sm:p-12 lg:flex lg:items-center lg:justify-between">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.22em] text-primary-300">Mulai Sekarang</p>
                        <h2 class="mt-3 font-heading text-3xl font-black tracking-tight sm:text-5xl">Buka pengalaman RestoApp langsung.</h2>
                        <p class="mt-4 max-w-2xl text-white/70">Coba alur pelanggan atau masuk sebagai staff untuk melihat operasional restoran dari sisi admin.</p>
                    </div>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row lg:mt-0">
                        <a href="{{ route('customer.app') }}" class="inline-flex items-center justify-center rounded-full bg-primary-500 px-7 py-4 text-sm font-black text-white transition hover:bg-primary-600">Customer App</a>
                        <a href="{{ route('admin.login') }}" class="inline-flex items-center justify-center rounded-full border border-white/20 px-7 py-4 text-sm font-black text-white transition hover:bg-white/10">Dashboard Staff</a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-gray-100 px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto flex max-w-7xl flex-col gap-4 text-sm font-semibold text-gray-500 sm:flex-row sm:items-center sm:justify-between">
                <p>&copy; {{ date('Y') }} RestoApp. Sistem restoran modern berbasis web.</p>
                <div class="flex gap-5">
                    <a href="{{ route('customer.app') }}" class="hover:text-primary-600">Customer App</a>
                    <a href="{{ route('admin.login') }}" class="hover:text-primary-600">Staff Login</a>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
