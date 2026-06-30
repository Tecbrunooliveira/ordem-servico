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
    <meta name="app-url" content="{{ \App\Support\Subdirectory::applicationUrl() }}">
    <meta name="wireui-icons-base" content="{{ \App\Support\Subdirectory::applicationUrl('/wireui/icons/outline/') }}">
    <script>
        (function () {
            var base = document.querySelector('meta[name="app-url"]')?.content?.replace(/\/$/, '') || '';

            if (! base) {
                return;
            }

            function rewriteWireuiUrl(url) {
                if (typeof url !== 'string') {
                    return url;
                }

                if (url.indexOf('/wireui/') === 0) {
                    return base + url;
                }

                try {
                    var parsed = new URL(url, window.location.origin);

                    if (parsed.pathname.indexOf('/wireui/') === 0 && parsed.pathname.indexOf(base + '/wireui/') !== 0) {
                        return base + parsed.pathname + parsed.search + parsed.hash;
                    }
                } catch (error) {
                    return url;
                }

                return url;
            }

            var nativeFetch = window.fetch.bind(window);

            window.fetch = function (input, init) {
                if (typeof input === 'string') {
                    return nativeFetch(rewriteWireuiUrl(input), init);
                }

                if (input instanceof Request) {
                    var nextUrl = rewriteWireuiUrl(input.url);

                    if (nextUrl !== input.url) {
                        input = new Request(nextUrl, input);
                    }
                }

                return nativeFetch(input, init);
            };
        })();
    </script>

    <title>{{ $title }} — {{ \App\Support\EmpresaConfig::branding()['nome'] }}</title>

    {!! \App\Support\ViteManifest::styles(['resources/css/app.css']) !!}
    @livewireStyles
    <link href="{{ asset('vendor/wireui/wireui.css') }}" rel="stylesheet" type="text/css">
</head>
<body class="h-full overflow-x-hidden bg-slate-50 text-slate-800 antialiased">
    @if (session('show_splash'))
        <x-layout.splash />
    @endif

    <x-dialog />
    <x-notifications z-index="z-[100]" />

    <div
        id="app-layout-shell"
        class="flex h-full min-h-screen overflow-x-hidden"
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

        <div id="app-layout-main" class="flex min-w-0 flex-1 flex-col overflow-x-hidden">
            <x-layout.header :title="$title" :subtitle="$subtitle" />

            <main class="app-main flex-1 overflow-y-auto overflow-x-hidden p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    {!! \App\Support\LivewireAssets::scripts() !!}
    <script src="{{ asset('vendor/wireui/wireui.js') }}"></script>
    <x-layout.wireui-subdirectory />
    {!! \App\Support\ViteManifest::scripts(['resources/js/app.js']) !!}
    <script>
        window.__patchWireuiNotifications?.();
    </script>
    @stack('scripts')
</body>
</html>
