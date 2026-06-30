@php
    $currentRoute = request()->route()?->getName();
    $empresa = \App\Support\EmpresaConfig::get();
    $tarefasAlertaCount = \App\Support\NotificationCenter::tarefasAlertaCount();
    $nomeMarca = ! empty($empresa['razao_social'])
        ? $empresa['razao_social']
        : ($empresa['nome_empresa'] ?? config('navigation.brand.name'));
@endphp

<aside
    id="app-sidebar"
    class="fixed inset-y-0 left-0 z-30 flex h-screen w-64 shrink-0 -translate-x-full flex-col overflow-hidden text-sidebar-text transition-all duration-300 ease-in-out lg:relative lg:z-auto lg:w-[4.5rem] lg:shrink-0 lg:translate-x-0"
    :class="sidebarOpen ? '!translate-x-0 lg:!w-64' : ''"
>
    <div
        class="sidebar-brand flex shrink-0 items-center border-b border-[#005300]/15 transition-all duration-300"
        :class="sidebarOpen ? 'gap-3 px-5 py-5' : 'justify-center px-2 py-4'"
    >
        <div class="sidebar-brand__logo flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-[#005300] to-[#004200] text-white shadow-md shadow-[#005300]/25 ring-1 ring-[#005300]/20">
            @if (! empty($empresa['logo']))
                <img src="{{ $empresa['logo'] }}" alt="{{ $nomeMarca }}" class="h-8 w-8 object-contain">
            @else
                <x-icon name="wrench-screwdriver" class="h-5 w-5" />
            @endif
        </div>
        <div class="min-w-0 overflow-hidden" x-show="sidebarOpen" x-cloak>
            <p class="truncate text-sm font-bold tracking-tight text-slate-900">{{ $nomeMarca }}</p>
            <p class="truncate text-xs font-medium text-[#005300]/70">{{ config('navigation.brand.subtitle') }}</p>
        </div>
    </div>

    <nav
        class="sidebar-nav min-h-0 flex-1 overflow-y-auto overflow-x-hidden py-4 transition-all duration-300"
        :class="sidebarOpen ? 'px-3' : 'px-2'"
    >
        <div :class="sidebarOpen ? 'space-y-6' : 'space-y-2'">
            @foreach (config('navigation.items') as $item)
                @php
                    $active = $currentRoute === $item['route'];
                    $canView = empty($item['permission']) || (auth()->check() && auth()->user()->can($item['permission']));
                @endphp
                @if ($canView)
                    <x-layout.sidebar-link
                        :href="Route::has($item['route']) ? route($item['route']) : '#'"
                        :icon="$item['icon']"
                        :label="$item['label']"
                        :active="$active"
                        :badge="$item['badge'] ?? null"
                    />
                @endif
            @endforeach

            @foreach (config('navigation.sections') as $section)
                <div class="sidebar-section">
                    <p
                        class="sidebar-section__title"
                        x-show="sidebarOpen"
                        x-cloak
                    >
                        {{ $section['title'] }}
                    </p>
                    <div class="space-y-1">
                        @foreach ($section['items'] as $item)
                            @php
                                $active = $currentRoute === $item['route'];
                                $canView = empty($item['permission']) || ! auth()->check() || auth()->user()->can($item['permission']);
                                $href = is_string($item['route']) && str_starts_with($item['route'], '#')
                                    ? '#'
                                    : (Route::has($item['route']) ? route($item['route']) : '#');
                            @endphp
                            @if ($canView)
                                <x-layout.sidebar-link
                                    :href="$href"
                                    :icon="$item['icon']"
                                    :label="$item['label']"
                                    :active="$active"
                                    :badge="$item['route'] === 'tarefas.index' && $tarefasAlertaCount > 0 ? $tarefasAlertaCount : ($item['badge'] ?? null)"
                                />
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </nav>

    <div
        class="shrink-0 border-t border-[#005300]/15 bg-white/45 backdrop-blur-sm transition-all duration-300"
        :class="sidebarOpen ? 'p-3' : 'p-2'"
    >
        <form
            method="POST"
            action="{{ route('logout') }}"
            :class="sidebarOpen ? '' : 'flex justify-center'"
        >
            @csrf
            <x-layout.sidebar-link
                tag="button"
                icon="arrow-right-start-on-rectangle"
                label="Sair"
                :danger="true"
                class="w-full"
            />
        </form>
    </div>
</aside>
