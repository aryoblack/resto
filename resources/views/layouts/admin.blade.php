<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — RestoApp Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script>
        window.restoAuthToken = @json(session('api_token'));
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="antialiased bg-gray-50 font-sans h-full" x-data="adminShell()" x-init="init()">

    <div class="flex h-screen overflow-hidden">

        {{-- Sidebar --}}
        <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 text-white flex flex-col flex-shrink-0 overflow-y-auto transition-transform duration-200 lg:static lg:z-auto lg:translate-x-0"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
               id="admin-sidebar">

            {{-- Logo --}}
            <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-800">
                <div class="w-9 h-9 rounded-xl bg-primary flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4z"/>
                        <path d="M3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                    </svg>
                </div>
                <div>
                    <div class="font-heading font-bold text-white">RestoApp</div>
                    <div class="text-xs text-gray-400">Admin Panel</div>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 px-3 py-4 space-y-1">
                @php
                    $currentRole = auth()->user()->role ?? null;
                    $navItems = [
                        ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'roles' => ['admin'], 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                        ['route' => 'admin.orders', 'label' => 'Pesanan Live', 'roles' => ['admin', 'waiter', 'chef'], 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                        ['route' => 'admin.cashier', 'label' => 'Kasir', 'roles' => ['admin', 'waiter'], 'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                        ['route' => 'admin.menus', 'label' => 'Menu', 'roles' => ['admin'], 'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16'],
                        ['route' => 'admin.categories', 'label' => 'Kategori', 'roles' => ['admin'], 'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
                        ['route' => 'admin.tables', 'label' => 'Meja', 'roles' => ['admin'], 'icon' => 'M3 10h18M3 14h18M10 3v18M14 3v18'],
                        ['route' => 'admin.inventory', 'label' => 'Stok', 'roles' => ['admin'], 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                        ['route' => 'admin.suppliers', 'label' => 'Supplier', 'roles' => ['admin'], 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                        ['route' => 'admin.promos', 'label' => 'Promo & Voucher', 'roles' => ['admin'], 'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
                        ['route' => 'admin.reservations', 'label' => 'Reservasi', 'roles' => ['admin'], 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                        ['route' => 'admin.reports', 'label' => 'Laporan', 'roles' => ['admin'], 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                        ['route' => 'admin.staff', 'label' => 'Karyawan', 'roles' => ['admin'], 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                        ['route' => 'admin.settings', 'label' => 'Pengaturan', 'roles' => ['admin'], 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
                    ];
                @endphp

                @foreach($navItems as $item)
                    @continue(! in_array($currentRole, $item['roles'], true))
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                              {{ request()->routeIs($item['route']) ? 'bg-primary text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                        </svg>
                        {{ $item['label'] }}
                        @if($item['route'] === 'admin.orders')
                            <span x-show="newOrderCount > 0"
                                  x-text="newOrderCount"
                                  class="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full animate-pulse">
                            </span>
                        @endif
                    </a>
                @endforeach

                {{-- KDS Link --}}
                @if(in_array($currentRole, ['admin', 'chef'], true))
                <a href="{{ route('kds.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-400 hover:bg-gray-800 hover:text-white transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    KDS Dapur
                </a>
                @endif
            </nav>

            {{-- User Info --}}
            <div class="border-t border-gray-800 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-primary-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                        {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-white truncate">{{ auth()->user()->name ?? 'Admin' }}</div>
                        <div class="text-xs text-gray-400">Administrator</div>
                    </div>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-white p-1 rounded transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Mobile sidebar overlay --}}
        <div x-show="sidebarOpen"
             @click="sidebarOpen = false"
             class="fixed inset-0 z-40 bg-black/50 lg:hidden">
        </div>

        {{-- Main Area --}}
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            {{-- Top Bar --}}
            <header class="bg-white border-b border-gray-200 px-4 py-4 flex items-center justify-between flex-shrink-0 sm:px-6">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <h1 class="text-lg font-heading font-semibold text-text sm:text-xl">@yield('title', 'Dashboard')</h1>
                </div>
                <div class="flex items-center gap-3">
                    @include('components.websocket-status')
                    {{-- Notification Bell --}}
                    <button class="relative p-2 text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span x-show="newOrderCount > 0" class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full animate-ping"></span>
                    </button>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 overflow-y-auto p-4 sm:p-6" id="admin-main-content">
                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    {{-- Global Dialog --}}
    <div x-show="dialog.open"
         x-cloak
         @keydown.escape.window="closeDialog(false)"
         class="fixed inset-0 z-[100] flex items-end justify-center bg-gray-900/60 p-0 backdrop-blur-sm sm:items-center sm:p-4"
         style="display: none;">
        <div class="absolute inset-0" @click="closeDialog(false)"></div>
        <section x-show="dialog.open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="translate-y-6 opacity-0 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
                 x-transition:leave-end="translate-y-6 opacity-0 sm:translate-y-0 sm:scale-95"
                 class="relative w-full overflow-hidden rounded-t-3xl bg-white shadow-2xl ring-1 ring-black/5 sm:rounded-3xl"
                 style="max-width: 32rem;">
            <div class="absolute inset-x-0 top-0 h-1" :class="dialog.variant === 'success' ? 'bg-green-500' : (dialog.variant === 'danger' ? 'bg-red-500' : 'bg-primary-500')"></div>
            <div class="p-5 sm:p-6">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl ring-1"
                         :class="dialog.variant === 'success' ? 'bg-green-50 text-green-600 ring-green-100' : (dialog.variant === 'danger' ? 'bg-red-50 text-red-500 ring-red-100' : 'bg-primary-50 text-primary-600 ring-primary-100')">
                        <svg x-show="dialog.variant === 'success'" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        <svg x-show="dialog.variant === 'danger'" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path></svg>
                        <svg x-show="dialog.variant !== 'success' && dialog.variant !== 'danger'" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 20a8 8 0 100-16 8 8 0 000 16z"></path></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-black uppercase tracking-wider"
                           :class="dialog.variant === 'success' ? 'text-green-600' : (dialog.variant === 'danger' ? 'text-red-500' : 'text-primary-600')"
                           x-text="dialog.eyebrow"></p>
                        <h3 class="mt-1 text-xl font-black text-gray-900" x-text="dialog.title"></h3>
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-gray-500" x-text="dialog.message"></p>
                    </div>
                    <button @click="closeDialog(false)" class="rounded-full bg-gray-100 p-2 text-gray-500 transition hover:bg-gray-200 hover:text-gray-900" aria-label="Tutup">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="mt-6 grid gap-3" :class="dialog.mode === 'confirm' ? 'grid-cols-2' : 'grid-cols-1'">
                    <button x-show="dialog.mode === 'confirm'" @click="closeDialog(false)" class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-black text-gray-700 transition hover:bg-gray-50" x-text="dialog.cancelText"></button>
                    <button @click="closeDialog(true)" class="rounded-xl px-4 py-3 text-sm font-black text-white shadow-lg transition"
                            :class="dialog.variant === 'danger' ? 'bg-red-500 shadow-red-500/20 hover:bg-red-600' : (dialog.variant === 'success' ? 'bg-green-600 shadow-green-500/20 hover:bg-green-700' : 'bg-primary-600 shadow-primary-500/20 hover:bg-primary-700')"
                            x-text="dialog.confirmText"></button>
                </div>
            </div>
        </section>
    </div>

    {{-- New Order Audio --}}
    <audio id="new-order-sound" preload="auto">
        <source src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq3EzGkGg2c6wdDUqVZLN1caDZERAkszR0cKEZEdPm9fW2sq" type="audio/wav">
    </audio>

    <script>
    window.restoAuthHeaders = function(headers = {}) {
        return {
            ...headers,
            ...(window.restoAuthToken ? { 'Authorization': 'Bearer ' + window.restoAuthToken } : {}),
        };
    };

    function adminShell() {
        return {
            sidebarOpen: false,
            newOrderCount: 0,
            dialog: {
                open: false,
                mode: 'alert',
                variant: 'info',
                eyebrow: 'Informasi',
                title: '',
                message: '',
                confirmText: 'Mengerti',
                cancelText: 'Batal',
                resolver: null,
            },
            init() {
                window.restoAlert = (options = {}) => this.openDialog({ ...options, mode: 'alert' });
                window.restoConfirm = (options = {}) => this.openDialog({ ...options, mode: 'confirm' });
                window.restoCloseDialog = () => {
                    const wasOpen = this.dialog.open;
                    this.dialog.open = false;
                    this.dialog.resolver = null;

                    return wasOpen;
                };

                // Laravel Echo for real-time notifications
                if (window.Echo) {
                    window.Echo.private('orders')
                        .listen('.order.created', (e) => {
                            this.newOrderCount++;
                            this.playNotificationSound();
                            this.showBrowserNotification('Pesanan Baru!', `Pesanan #${e.order?.order_number || e.order?.id} baru saja masuk.`);
                        });

                    window.Echo.private('admin')
                        .listen('.stock.critical', (e) => {
                            this.showBrowserNotification('⚠️ Stok Kritis!', `${e.ingredient?.ingredient_name} hampir habis.`);
                        });
                }
            },
            playNotificationSound() {
                const audio = document.getElementById('new-order-sound');
                if (audio) audio.play().catch(() => {});
            },
            openDialog(options = {}) {
                const variant = options.variant || (options.danger ? 'danger' : 'info');
                this.dialog = {
                    open: true,
                    mode: options.mode || 'alert',
                    variant,
                    eyebrow: options.eyebrow || (variant === 'danger' ? 'Perlu Konfirmasi' : (variant === 'success' ? 'Berhasil' : 'Informasi')),
                    title: options.title || 'Informasi',
                    message: options.message || '',
                    confirmText: options.confirmText || (options.mode === 'confirm' ? 'Lanjutkan' : 'Mengerti'),
                    cancelText: options.cancelText || 'Batal',
                    resolver: null,
                };

                return new Promise((resolve) => {
                    this.dialog.resolver = resolve;
                });
            },
            closeDialog(result) {
                const resolver = this.dialog.resolver;
                this.dialog.open = false;
                this.dialog.resolver = null;
                if (resolver) resolver(Boolean(result));
            },
            showBrowserNotification(title, body) {
                if ('Notification' in window && Notification.permission === 'granted') {
                    new Notification(title, { body, icon: '/icons/icon-192x192.png' });
                }
            }
        };
    }
    </script>

    @stack('scripts')
</body>
</html>
