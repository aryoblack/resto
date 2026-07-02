<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'KDS - RestoApp')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script>
        window.restoAuthToken = @json(session('api_token'));
        window.restoAuthHeaders = function(headers = {}) {
            return {
                ...headers,
                ...(window.restoAuthToken ? { 'Authorization': 'Bearer ' + window.restoAuthToken } : {}),
            };
        };
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #111827; /* Dark background for kitchen visibility */
            color: #F9FAFB;
            overflow-x: hidden;
        }
        /* Custom scrollbar for dark theme */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #1F2937;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb {
            background: #4B5563;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #6B7280;
        }
    </style>
    @stack('styles')
</head>
<body class="antialiased h-screen flex flex-col">

    <!-- Navbar -->
    <header class="flex flex-shrink-0 flex-wrap items-center justify-between gap-3 border-b border-gray-800 bg-gray-900 px-4 py-3 shadow-md sm:px-6">
        <div class="flex min-w-0 flex-wrap items-center gap-3 sm:gap-4">
            <h1 class="text-lg font-bold uppercase tracking-wider text-white sm:text-xl">Resto KDS</h1>
            <div class="hidden h-6 w-px bg-gray-700 sm:block"></div>
            <div class="flex min-w-0 gap-2">
                <span class="rounded-lg bg-gray-800 px-3 py-1 text-sm font-medium text-gray-300">Station: <span class="text-primary-400">Semua (Dapur & Bar)</span></span>
            </div>
        </div>
        <div class="ml-auto flex items-center gap-4 sm:gap-6">
            <div class="text-sm font-medium text-gray-400" id="clock">00:00:00</div>
            @if((auth()->user()->role ?? null) === 'admin')
                <a href="{{ route('admin.dashboard') }}" class="text-gray-400 hover:text-white transition-colors" title="Kembali ke Dashboard">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                </a>
            @endif
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="text-gray-400 hover:text-white transition-colors" title="Keluar">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                </button>
            </form>
        </div>
    </header>

    <main class="flex-1 overflow-hidden">
        @yield('content')
    </main>

    <script>
        // Simple Clock
        setInterval(() => {
            const now = new Date();
            document.getElementById('clock').innerText = now.toLocaleTimeString('id-ID');
        }, 1000);
    </script>
    @stack('scripts')
</body>
</html>
