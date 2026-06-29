@props([
    'title' => 'Opções',
    'width' => 'w-56',
])

<div
    {{ $attributes->class(['inline-flex']) }}
    x-data="{
        open: false,
        menuStyle: '',
        toggle() {
            this.open = ! this.open;
            if (this.open) {
                this.$nextTick(() => this.positionMenu());
            }
        },
        close() {
            this.open = false;
        },
        positionMenu() {
            const trigger = this.$refs.trigger;
            const menu = this.$refs.menu;

            if (! trigger || ! menu) {
                return;
            }

            const rect = trigger.getBoundingClientRect();
            const menuHeight = menu.offsetHeight || 260;
            const menuWidth = menu.offsetWidth || 224;
            const gap = 6;
            const padding = 8;
            let top = rect.bottom + gap;

            if (top + menuHeight > window.innerHeight - padding) {
                top = rect.top - menuHeight - gap;
            }

            top = Math.max(padding, Math.min(top, window.innerHeight - menuHeight - padding));

            let left = rect.right - menuWidth;
            left = Math.max(padding, Math.min(left, window.innerWidth - menuWidth - padding));

            this.menuStyle = 'position:fixed;top:' + top + 'px;left:' + left + 'px;z-index:70;';
        },
    }"
    x-on:scroll.window="open && positionMenu()"
    x-on:resize.window="open && positionMenu()"
>
    <button
        type="button"
        x-ref="trigger"
        x-on:click="toggle()"
        title="{{ $title }}"
        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 ring-1 ring-slate-200 hover:bg-slate-100 hover:text-brand-600"
    >
        @isset($trigger)
            {{ $trigger }}
        @else
            <x-icon name="ellipsis-vertical" class="h-4 w-4" />
        @endisset
    </button>

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            x-ref="menu"
            x-bind:style="menuStyle"
            x-on:click.outside="close()"
            x-on:keydown.escape.window="close()"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @class([
                'rounded-lg border border-slate-200 bg-white p-1 shadow-lg',
                $width,
            ])
            style="display: none;"
            role="menu"
        >
            @if ($title)
                <div class="border-b border-slate-100 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    {{ $title }}
                </div>
            @endif

            {{ $slot }}
        </div>
    </template>
</div>
