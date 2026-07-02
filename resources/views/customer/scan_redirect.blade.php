<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Menyiapkan Meja...</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-primary-600 h-screen flex flex-col items-center justify-center text-white">
    <svg class="animate-spin h-10 w-10 text-white mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
    <h1 class="text-xl font-bold">Menyiapkan Meja Anda...</h1>
    
    <script>
        // Save table ID to localStorage for the PWA SPA to read
        const tableSessionExpiresAt = Date.now() + (6 * 60 * 60 * 1000);
        localStorage.setItem('table_id', '{{ $table_id }}');
        localStorage.setItem('table_id_expires_at', String(tableSessionExpiresAt));
        localStorage.setItem('table_id_allow_occupied_until', String(tableSessionExpiresAt));
        
        // Redirect to the PWA home
        setTimeout(() => {
            window.location.href = '/app';
        }, 800);
    </script>
</body>
</html>
