@php
    $currentRoute = request()->route()?->getName();

    $linkClasses = fn (bool $active) => $active
        ? 'bg-[#005300]/12 text-[#004200] shadow-sm ring-1 ring-[#005300]/15'
        : 'text-slate-600 hover:bg-[#005300]/8 hover:text-[#004200]';
@endphp

<aside
    id="app-sidebar"
    class="fixed inset-y-0 left-0 z-30 flex h-screen w-64 shrink-0 -translate-x-full flex-col overflow-hidden text-sidebar-text transition-all duration-300 ease-in-out lg:relative lg:z-auto lg:w-[4.5rem] lg:shrink-0 lg:translate-x-0"
    :class="sidebarOpen ? '!translate-x-0 lg:!w-64' : ''"
>
    <div
        class="flex shrink-0 items-center border-b border-[#005300]/10 py-5 transition-all duration-300"
        :class="sidebarOpen ? 'gap-3 px-5' : 'justify-center px-2'"
    >
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#005300]/10 text-[#005300] shadow-sm ring-1 ring-[#005300]/15">
            <x-icon name="wrench-screwdriver" class="h-5 w-5" />
        </div>
        <div class="min-w-0 overflow-hidden" x-show="sidebarOpen" x-cloak>
            <p class="truncate text-sm font-semibold text-slate-900">{{ config('navigation.brand.name') }}</p>
            <p class="truncate text-xs text-slate-500">{{ config('navigation.brand.subtitle') }}</p>
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
                    <a
                        href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
                        title="{{ $item['label'] }}"
                        @class([
                            'flex items-center rounded-lg py-2.5 text-sm font-medium transition-colors',
                            $linkClasses($active),
                        ])
                        :class="sidebarOpen ? 'gap-3 px-3' : 'justify-center px-2'"
                    >
                        <x-icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                        <span class="truncate" x-show="sidebarOpen" x-cloak>{{ $item['label'] }}</span>
                    </a>
                @endif
            @endforeach

            @foreach (config('navigation.sections') as $section)
                <div>
                    <p
                        class="mb-2 px-3 text-[10px] font-semibold uppercase tracking-wider text-[#005300]/55"
                        x-show="sidebarOpen"
                        x-cloak
                    >
                        {{ $section['title'] }}
                    </p>
                    <div class="space-y-0.5">
                        @foreach ($section['items'] as $item)
                            @php
                                $active = $currentRoute === $item['route'];
                                $canView = empty($item['permission']) || ! auth()->check() || auth()->user()->can($item['permission']);
                            @endphp
                            @if ($canView)
                                <a
                                    href="{{ is_string($item['route']) && str_starts_with($item['route'], '#') ? '#' : (Route::has($item['route']) ? route($item['route']) : '#') }}"
                                    title="{{ $item['label'] }}"
                                    @class([
                                        'relative flex items-center rounded-lg py-2.5 text-sm font-medium transition-colors',
                                        $linkClasses($active),
                                    ])
                                    :class="sidebarOpen ? 'gap-3 px-3' : 'justify-center px-2'"
                                >
                                    <x-icon :name="$item['icon']" class="h-4 w-4 shrink-0 opacity-90" />
                                    <span class="flex-1 truncate" x-show="sidebarOpen" x-cloak>{{ $item['label'] }}</span>
                                    @if (! empty($item['badge']))
                                        <span
                                            x-show="sidebarOpen"
                                            x-cloak
                                            @class([
                                                'shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold',
                                                'bg-[#005300] text-white' => $active,
                                                'bg-[#005300]/12 text-[#004200]' => ! $active,
                                            ])
                                        >
                                            {{ $item['badge'] }}
                                        </span>
                                        <span
                                            x-show="! sidebarOpen"
                                            x-cloak
                                            class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-[#005300] ring-2 ring-white"
                                        ></span>
                                    @endif
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </nav>

    <div
        class="shrink-0 border-t border-[#005300]/10 transition-all duration-300"
        :class="sidebarOpen ? 'p-4' : 'p-2'"
    >
        <form
            method="POST"
            action="{{ route('logout') }}"
            :class="sidebarOpen ? '' : 'flex justify-center'"
        >
            @csrf
            <button
                type="submit"
                title="Sair"
                class="flex w-full items-center rounded-lg py-2.5 text-sm font-medium text-slate-600 transition-colors hover:bg-red-50 hover:text-red-600"
                :class="sidebarOpen ? 'gap-3 px-3' : 'justify-center px-2'"
            >
                <x-icon name="arrow-right-start-on-rectangle" class="h-5 w-5 shrink-0" />
                <span class="truncate" x-show="sidebarOpen" x-cloak>Sair</span>
            </button>
        </form>
    </div>
</aside>
