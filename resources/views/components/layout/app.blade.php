@props([
    'title' => 'Dashboard',
    'subtitle' => 'Visão geral das atividades e projetos.',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full" style="color-scheme: light;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ rtrim(config('app.url'), '/') }}">

    <title>{{ $title }} — {{ config('navigation.brand.name') }}</title>

    {!! \App\Support\ViteManifest::styles(['resources/css/app.css']) !!}
    @livewireStyles
    <link href="{{ asset('vendor/wireui/wireui.css') }}" rel="stylesheet" type="text/css">
</head>
<body class="h-full bg-slate-50 text-slate-800 antialiased">
    @if (session('show_splash'))
        <x-layout.splash />
    @endif

    <x-dialog />
    <x-notifications z-index="z-[100]" />

    <div
        id="app-layout-shell"
        class="flex h-full min-h-screen"
        x-data="{ sidebarOpen: false }"
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

        <div id="app-layout-main" class="flex min-w-0 flex-1 flex-col">
            <x-layout.header :title="$title" :subtitle="$subtitle" />

            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    {!! \App\Support\LivewireAssets::scripts() !!}
    {!! \App\Support\ViteManifest::scripts(['resources/js/app.js']) !!}
    <script src="{{ asset('vendor/wireui/wireui.js') }}" defer></script>
    <script>
        window.Wireui = {
            cache: {},
            hook(hook, callback) {
                window.addEventListener(`wireui:${hook}`, () => callback())
            },
            dispatchHook(hook) {
                window.dispatchEvent(new Event(`wireui:${hook}`))
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
