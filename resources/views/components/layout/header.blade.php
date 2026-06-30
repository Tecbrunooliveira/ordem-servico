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
    <div class="flex items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-4">
            <button
                type="button"
                x-on:click="sidebarOpen = ! sidebarOpen"
                class="inline-flex rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
                :aria-expanded="sidebarOpen"
                aria-controls="app-sidebar"
                aria-label="Alternar menu lateral"
            >
                <x-icon name="bars-3" class="h-5 w-5" />
            </button>
            <div>
                <h1 class="text-xl font-semibold text-slate-900">{{ $title }}</h1>
                <p class="text-sm text-slate-500">{{ $subtitle }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="relative hidden sm:block">
                <x-icon name="magnifying-glass" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                    type="search"
                    placeholder="Buscar..."
                    class="w-56 rounded-lg border border-slate-200 bg-slate-50 py-2 pl-9 pr-12 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 lg:w-64"
                >
                <kbd class="pointer-events-none absolute right-2 top-1/2 hidden -translate-y-1/2 rounded border border-slate-200 bg-white px-1.5 py-0.5 text-[10px] text-slate-400 lg:inline">⌘K</kbd>
            </div>

            <button type="button" class="relative rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">
                <x-icon name="bell" class="h-5 w-5" />
                <span class="absolute right-1 top-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">5</span>
            </button>

            <div class="flex items-center gap-3 border-l border-slate-200 pl-3">
                <img
                    src="{{ $avatarUrl }}"
                    alt="{{ $nomeUsuario }}"
                    title="{{ $nomeUsuario }}"
                    class="h-9 w-9 shrink-0 rounded-full ring-2 ring-slate-100"
                >
                <div class="hidden min-w-0 sm:block">
                    <p class="truncate text-sm font-medium text-slate-900">{{ $nomeUsuario }}</p>
                    <p class="truncate text-xs text-slate-500">{{ $papelUsuario }}</p>
                </div>
            </div>
        </div>
    </div>
</header>
