<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#FF7A2F">
    <link rel="manifest" href="/manifest.json">
    
    <!-- PWA iOS Support -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="RestoApp">
    
    <title>@yield('title', 'RestoApp')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        /* Mobile app styling fixes */
        body {
            -webkit-tap-highlight-color: transparent;
            overscroll-behavior-y: none; /* Prevent pull to refresh on whole body */
        }
        /* Hide scrollbars for cleaner UI but keep functionality */
        ::-webkit-scrollbar {
            display: none;
        }
        * {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .pb-safe {
            padding-bottom: env(safe-area-inset-bottom, 20px);
        }
        [x-cloak] {
            display: none !important;
        }
    </style>
    @stack('styles')
</head>
<body class="antialiased bg-gray-50 font-sans h-screen flex flex-col overflow-hidden">

    @yield('content')

    <script>
        // Register Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').then(registration => {
                    console.log('SW registered:', registration.scope);
                }).catch(error => {
                    console.log('SW registration failed:', error);
                });
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
