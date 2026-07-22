<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8" />
        {{-- `nativephp_call` only exists inside the on-device runtime; this flag is
             the reliable "running as native app" signal for the JS bridge. --}}
        <script>
            window.__nativephp = @json(function_exists('nativephp_call'));
        </script>
        {{-- Resolve the theme before first paint: `system` follows the device via prefers-color-scheme. --}}
        <script>
            (function (theme) {
                var dark = theme === 'dark'
                    || (theme !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
            })(@json($page['props']['theme'] ?? 'system'));
        </script>
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
        <meta name="theme-color" content="#3c5e4d" />
        <title inertia>{{ config('app.name', 'Mi Economía') }}</title>
        @routes
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="h-full bg-surface font-sans text-ink antialiased">
        @inertia
    </body>
</html>
