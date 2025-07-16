<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" :class="{ 'dark': isDark }">
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('themeSwitcher', () => ({
            isDark: false,
            toggleTheme() {
                this.isDark = !this.isDark;
                document.documentElement.classList.toggle('dark', this.isDark);
                localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
            },
            init() {
                this.isDark = localStorage.getItem('theme') === 'dark' ||
                    (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', this.isDark);
            }
        }));
    });
</script>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('imagens/logo-pegou-promo.png') }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @notifyCss
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/flowbite@2.2.1/dist/flowbite.min.js"></script>
</head>

<body x-data="themeSwitcher()" x-init="init()" class="font-sans antialiased bg-lightbg2 dark:bg-bodybg2">
    <div class="min-h-screen flex flex-col">
        @include('layouts.navigation')
        <x-breadcrumbs.breadcrumb />

        @isset($header)
            <header class="bg-lightbg2 dark:bg-bodybg2 shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main class="flex-grow dark:bg-bodybg2 bg-lightbg2">
            {{ $slot }}
        </main>

        <footer class="bg-lightbg dark:bg-bodybg border-t border-gray-200 dark:border-bodybg2">
            <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 text-center text-gray-500 dark:text-gray-400">
                &copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}. Todos os direitos reservados.
            </div>
        </footer>
    </div>


    <x-notify::notify />
    <x-arrow-to-start />
    <x-arrow-to-end />

</body>

</html>
