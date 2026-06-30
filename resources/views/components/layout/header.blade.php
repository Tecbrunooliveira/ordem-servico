@props([
    'title' => 'Dashboard',
    'subtitle' => 'Visão geral das atividades e projetos.',
])

@php
    $usuario = auth()->user();
    $nomeUsuario = $usuario?->nome ?? 'Usuário';
    $papelUsuario = $usuario?->getRoleNames()->first() ?? 'Usuário';
    $avatarUrl = $usuario
        ? 'https://ui-avatars.com/api/?name='.urlencode($nomeUsuario).'&background=3b82f6&color=fff'
        : 'https://ui-avatars.com/api/?name=Usuario&background=3b82f6&color=fff';
@endphp

<header class="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur-md">
    <div class="flex min-w-0 items-center justify-between gap-3 px-4 py-3 sm:gap-4 sm:px-6 sm:py-4 lg:px-8">
        <div class="flex min-w-0 flex-1 items-center gap-3 sm:gap-4">
            <button
                type="button"
                x-on:click="sidebarOpen = ! sidebarOpen"
                class="app-touch-target inline-flex shrink-0 rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
                :aria-expanded="sidebarOpen"
                aria-controls="app-sidebar"
                aria-label="Alternar menu lateral"
            >
                <x-icon name="bars-3" class="h-5 w-5" />
            </button>
            <div class="min-w-0">
                <h1 class="truncate text-lg font-semibold text-slate-900 sm:text-xl">{{ $title }}</h1>
                <p class="truncate text-xs text-slate-500 sm:text-sm">{{ $subtitle }}</p>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
            <div class="relative hidden md:block">
                <x-icon name="magnifying-glass" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                    type="search"
                    placeholder="Buscar..."
                    class="w-44 rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-9 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 lg:w-64 lg:pr-12"
                >
                <kbd class="pointer-events-none absolute right-2 top-1/2 hidden -translate-y-1/2 rounded border border-slate-200 bg-white px-1.5 py-0.5 text-[10px] text-slate-400 xl:inline">⌘K</kbd>
            </div>

            <livewire:layout.notification-center />

            <div class="flex items-center gap-2 border-l border-slate-200 pl-2 sm:gap-3 sm:pl-3">
                <img
                    src="{{ $avatarUrl }}"
                    alt="{{ $nomeUsuario }}"
                    title="{{ $nomeUsuario }}"
                    class="h-9 w-9 shrink-0 rounded-full ring-2 ring-slate-100 sm:h-10 sm:w-10"
                >
                <div class="hidden min-w-0 md:block">
                    <p class="max-w-[8rem] truncate text-sm font-medium text-slate-900 lg:max-w-[10rem]">{{ $nomeUsuario }}</p>
                    <p class="max-w-[8rem] truncate text-xs text-slate-500 lg:max-w-[10rem]">{{ $papelUsuario }}</p>
                </div>
            </div>
        </div>
    </div>
</header>
