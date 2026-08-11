<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- <title inertia>{{ config('app.name', 'Laravel') }}</title> --}}
        <title inertia>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}?v=2" type="image/x-icon">
        
        <!-- PWA Configuration -->
        @if (request()->is('admin*'))
            <link rel="manifest" href="{{ asset('manifest.json') }}?v=2">
            <meta name="theme-color" content="#1f2937">
            <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v=2">
        @endif
        
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        {{-- <script src="https://app.sandbox.xendit.co/snap/snap.js" data-client-key="{{ config('xendit.client_key') }}"></script> --}}
        @auth
            {{-- Jika ada user yang login --}}
            @if (auth()->user()->hasRole('admin'))
                {{-- Jika dia adalah admin, berikan semua rute --}}
                @routes
            @elseif (auth()->user()->hasRole('siswa'))
                {{-- Jika dia adalah siswa, berikan rute siswa dan rute publik --}}
                @routes(['siswa.*', 'public.*', 'logout', 'profile.*', 'dashboard'])
            @else
                {{-- Untuk role lain jika ada, berikan rute publik saja --}}
                @routes(['public.*', 'login', 'register', 'logout'])
            @endif
        @else
            {{-- Jika tidak ada yang login (tamu), berikan HANYA rute publik yang aman --}}
            @routes(['tagihan.check_form', 'tagihan.check_status', 'pendaftaran.*', 'login', 'register'])
        @endauth
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
        
        <!-- PWA Service Worker Registration -->
        @if (request()->is('admin*'))
            <script>
                if ('serviceWorker' in navigator) {
                    window.addEventListener('load', () => {
                        navigator.serviceWorker.register('/sw.js').then(registration => {
                            console.log('SW registered: ', registration);
                        }).catch(registrationError => {
                            console.log('SW registration failed: ', registrationError);
                        });
                    });
                }
            </script>
        @endif
    </body>
</html>
