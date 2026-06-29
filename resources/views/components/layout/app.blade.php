@props([
    'title' => 'Dashboard',
    'subtitle' => 'Visão geral das atividades e projetos.',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }} — {{ config('navigation.brand.name') }}</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <wireui:styles />
</head>
<body class="h-full bg-slate-50 text-slate-800 antialiased">
    @if (session('show_splash'))
        <x-layout.splash />
    @endif

    <x-dialog />
    <x-notifications z-index="z-[100]" />

    <div
        class="flex h-full min-h-screen"
        x-data="{ sidebarOpen: false }"
        x-cloak
    >
        <div
            x-show="sidebarOpen"
            x-transition:enter="transition-opacity ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-on:click="sidebarOpen = false"
            class="fixed inset-0 z-20 bg-slate-900/40 lg:hidden"
            aria-hidden="true"
        ></div>

        <x-layout.sidebar />

        <div class="flex min-w-0 flex-1 flex-col">
            <x-layout.header :title="$title" :subtitle="$subtitle" />

            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
    <wireui:scripts />
    @stack('scripts')
</body>
</html>
