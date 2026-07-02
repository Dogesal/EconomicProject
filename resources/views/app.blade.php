<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8" />
        {{-- Resolve the theme before first paint: `system` follows the device via prefers-color-scheme. --}}
        <script>
            (function (theme) {
                var dark = theme === 'dark'
                    || (theme !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
            })(@json($page['props']['theme'] ?? 'system'));
        </script>
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
        <meta name="theme-color" content="#4f46e5" />
        <title inertia>{{ config('app.name', 'Mi Economía') }}</title>
        @routes
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="h-full bg-slate-50 font-sans text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
        @inertia
    </body>
</html>
