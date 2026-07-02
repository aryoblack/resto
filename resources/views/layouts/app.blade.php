<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#FF7A2F">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', 'Pesan makanan favorit Anda dengan mudah dan cepat.')">

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">

    <title>@yield('title', 'Resto App') — RestoApp</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="antialiased bg-background font-sans" x-data="appShell()" x-init="init()">

    {{-- Offline Banner --}}
    <div x-show="!isOnline"
         x-transition
         class="fixed top-0 inset-x-0 z-[9999] bg-yellow-500 text-white text-center text-sm py-2 font-medium">
        ⚠️ Tidak ada koneksi internet. Beberapa fitur mungkin tidak tersedia.
    </div>

    {{-- Navigation Bar (Customer) --}}
    @unless(request()->routeIs('admin.*') || request()->routeIs('kds.*'))
    <nav class="fixed top-0 inset-x-0 z-50 bg-white/90 backdrop-blur-md border-b border-gray-100 shadow-sm">
        <div class="max-w-md mx-auto px-4 h-16 flex items-center justify-between">
            <a href="{{ route('customer.home') }}" class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 6a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2zm0 6a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2z"/>
                    </svg>
                </div>
                <span class="font-heading font-bold text-lg text-text">RestoApp</span>
            </a>

            <div class="flex items-center gap-3">
                {{-- WebSocket Status Indicator --}}
                @include('components.websocket-status')

                {{-- Cart Badge --}}
                <a href="{{ route('customer.cart') }}" class="relative p-2">
                    <svg class="w-6 h-6 text-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <span x-show="cartCount > 0"
                          x-text="cartCount"
                          class="absolute -top-1 -right-1 bg-primary text-white text-xs w-5 h-5 rounded-full flex items-center justify-center font-bold">
                    </span>
                </a>
            </div>
        </div>
    </nav>

    {{-- Bottom Navigation (Customer) --}}
    <nav class="fixed bottom-0 inset-x-0 z-50 bg-white border-t border-gray-100 safe-area-inset-bottom">
        <div class="max-w-md mx-auto flex">
            <a href="{{ route('customer.home') }}"
               class="flex-1 flex flex-col items-center py-3 gap-1 {{ request()->routeIs('customer.home') ? 'text-primary' : 'text-gray-400' }}">
                <svg class="w-5 h-5" fill="{{ request()->routeIs('customer.home') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="text-xs font-medium">Beranda</span>
            </a>
            <a href="{{ route('customer.orders') }}"
               class="flex-1 flex flex-col items-center py-3 gap-1 {{ request()->routeIs('customer.orders*') ? 'text-primary' : 'text-gray-400' }}">
                <svg class="w-5 h-5" fill="{{ request()->routeIs('customer.orders*') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span class="text-xs font-medium">Pesanan</span>
            </a>
            <a href="{{ route('customer.reservations') }}"
               class="flex-1 flex flex-col items-center py-3 gap-1 {{ request()->routeIs('customer.reservations*') ? 'text-primary' : 'text-gray-400' }}">
                <svg class="w-5 h-5" fill="{{ request()->routeIs('customer.reservations*') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="text-xs font-medium">Reservasi</span>
            </a>
            <a href="{{ route('customer.profile') }}"
               class="flex-1 flex flex-col items-center py-3 gap-1 {{ request()->routeIs('customer.profile*') ? 'text-primary' : 'text-gray-400' }}">
                <svg class="w-5 h-5" fill="{{ request()->routeIs('customer.profile*') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span class="text-xs font-medium">Profil</span>
            </a>
        </div>
    </nav>
    @endunless

    {{-- Floating Scan QR Button --}}
    @unless(request()->routeIs('admin.*') || request()->routeIs('kds.*'))
    <button id="btn-scan-qr"
            onclick="window.location.href='{{ route('customer.scan') }}'"
            class="fixed bottom-20 right-4 z-40 w-14 h-14 bg-primary text-white rounded-full shadow-xl flex items-center justify-center hover:bg-primary-600 active:scale-95 transition-all duration-200">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 3a4 4 0 10-8 0 4 4 0 008 0z"/>
        </svg>
    </button>
    @endunless

    {{-- Main Content --}}
    <main class="{{ request()->routeIs('admin.*') || request()->routeIs('kds.*') ? '' : 'pt-16 pb-20 max-w-md mx-auto' }}">
        @if(session('success'))
            <div class="mx-4 mt-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-input text-sm" x-data x-init="setTimeout(() => $el.remove(), 4000)">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mx-4 mt-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-input text-sm" x-data x-init="setTimeout(() => $el.remove(), 4000)">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    <script>
    function appShell() {
        return {
            isOnline: navigator.onLine,
            cartCount: 0,
            init() {
                window.addEventListener('online',  () => this.isOnline = true);
                window.addEventListener('offline', () => this.isOnline = false);
                this.loadCartCount();
                window.addEventListener('cart-updated', () => this.loadCartCount());
            },
            loadCartCount() {
                try {
                    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
                    this.cartCount = cart.reduce((sum, item) => sum + (item.qty || 0), 0);
                } catch { this.cartCount = 0; }
            }
        };
    }
    </script>

    @stack('scripts')
</body>
</html>
