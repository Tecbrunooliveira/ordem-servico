<?php

use App\Support\NotificationCenter;
use Livewire\Component;

new class extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->sync();
        $this->emitirNaoLidas();
    }

    public function poll(): void
    {
        $this->sync();
        $this->emitirNaoLidas();
    }

    public function abrirPainel(): void
    {
        $userId = auth()->id();

        if ($userId) {
            NotificationCenter::markAllAsRead($userId);
        }

        $this->sync();
    }

    public function marcarLida(string $id): void
    {
        $userId = auth()->id();

        if ($userId) {
            NotificationCenter::markAsRead($userId, $id);
        }

        $this->sync();
    }

    public function marcarTodasLidas(): void
    {
        $userId = auth()->id();

        if ($userId) {
            NotificationCenter::markAllAsRead($userId);
        }

        $this->sync();
    }

    private function emitirNaoLidas(): void
    {
        $unread = array_values(array_filter(
            $this->items,
            fn (array $item): bool => ! $item['read'],
        ));

        if ($unread !== []) {
            $this->dispatch('notificacoes-novas', items: $unread);
        }
    }

    private function sync(): void
    {
        $items = NotificationCenter::all();
        $this->items = $items;
        $this->unreadCount = NotificationCenter::unreadCount();
    }
};
?>

<div
    wire:poll.20s="poll"
    x-data="notificationCenterPanel()"
    x-on:keydown.escape.window="open && (open = false)"
    x-on:notificacoes-novas.window="notificationCenter.handleEvent($event.detail)"
    @tarefas-updated.window="$wire.poll()"
    class="relative"
>
    <div
        x-show="alarmRinging"
        x-cloak
        class="fixed right-4 top-20 z-[80] flex items-center gap-2 rounded-full border border-amber-300 bg-amber-50 px-3 py-2 shadow-lg sm:right-6"
    >
        <span class="relative flex h-2.5 w-2.5">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-amber-500"></span>
        </span>
        <span class="text-xs font-medium text-amber-900">Alarme ativo</span>
        <button
            type="button"
            x-on:click="silenceAlarm()"
            class="rounded-full bg-amber-600 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-amber-700"
        >
            Silenciar
        </button>
    </div>

    <button
        type="button"
        x-on:click="toggle()"
        class="relative rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
        :class="alarmRinging ? 'bg-amber-50 text-amber-700 ring-2 ring-amber-300 ring-offset-1' : ''"
        aria-label="Central de notificações"
        :aria-expanded="open"
    >
        <x-icon name="bell" class="h-5 w-5" />
        @if ($unreadCount > 0)
            <span class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        x-on:click.outside="open = false"
        class="absolute right-0 top-full z-50 mt-2 w-[22rem] origin-top-right overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl sm:w-96"
    >
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-900">Notificações</h3>
                <p class="text-xs text-slate-500">{{ count($items) }} aviso(s)</p>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    x-show="alarmRinging"
                    x-cloak
                    x-on:click="silenceAlarm()"
                    class="rounded-lg bg-amber-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-amber-700"
                >
                    Silenciar alarme
                </button>
                @if ($unreadCount > 0)
                    <button
                        type="button"
                        wire:click="marcarTodasLidas"
                        class="text-xs font-medium text-brand-600 hover:text-brand-700"
                    >
                        Marcar todas lidas
                    </button>
                @endif
            </div>
        </div>

        <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
            @forelse ($items as $notification)
                @php
                    $styles = match ($notification['type']) {
                        'warning' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-600', 'icon' => 'exclamation-triangle'],
                        'info' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'icon' => 'information-circle'],
                        'success' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-600', 'icon' => 'check-circle'],
                        default => ['bg' => 'bg-slate-100', 'text' => 'text-slate-600', 'icon' => 'bell'],
                    };
                @endphp
                <a
                    href="{{ $notification['url'] }}"
                    wire:click="marcarLida('{{ $notification['id'] }}')"
                    @class([
                        'flex gap-3 px-4 py-3 transition hover:bg-slate-50',
                        ! $notification['read'] ? 'bg-brand-50/40' : '',
                    ])
                >
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $styles['bg'] }} {{ $styles['text'] }}">
                        <x-icon :name="$styles['icon']" class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-sm font-medium text-slate-900">{{ $notification['title'] }}</p>
                            <span class="shrink-0 text-[11px] text-slate-400">{{ $notification['time'] }}</span>
                        </div>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $notification['message'] }}</p>
                    </div>
                </a>
            @empty
                <div class="px-4 py-10 text-center text-sm text-slate-500">
                    Nenhuma notificação no momento.
                </div>
            @endforelse
        </div>

        <div class="space-y-2 border-t border-slate-100 bg-slate-50 px-4 py-3">
            <div class="flex items-center justify-between gap-3">
                <span class="text-xs font-medium text-slate-600">Som ao receber</span>
                <button
                    type="button"
                    x-on:click="soundEnabled = notificationCenter.toggleSound()"
                    class="relative inline-flex h-6 w-11 shrink-0 rounded-full transition"
                    :class="soundEnabled ? 'bg-brand-600' : 'bg-slate-300'"
                >
                    <span
                        class="inline-block h-5 w-5 translate-y-0.5 rounded-full bg-white shadow transition"
                        :class="soundEnabled ? 'translate-x-5' : 'translate-x-0.5'"
                    ></span>
                </button>
            </div>

            <div class="flex items-center justify-between gap-3">
                <span class="text-xs font-medium text-slate-600">Push no navegador</span>
                <button
                    type="button"
                    x-on:click="togglePush()"
                    class="relative inline-flex h-6 w-11 shrink-0 rounded-full transition"
                    :class="pushEnabled ? 'bg-brand-600' : 'bg-slate-300'"
                >
                    <span
                        class="inline-block h-5 w-5 translate-y-0.5 rounded-full bg-white shadow transition"
                        :class="pushEnabled ? 'translate-x-5' : 'translate-x-0.5'"
                    ></span>
                </button>
            </div>

            <p class="text-[11px] leading-relaxed text-slate-500" x-show="pushPermission === 'denied'" x-cloak>
                Push bloqueado pelo navegador. Libere nas configurações do site.
            </p>
            <button
                type="button"
                x-show="pushPermission === 'default'"
                x-cloak
                x-on:click="requestPush()"
                class="text-xs font-medium text-brand-600 hover:text-brand-700"
            >
                Permitir notificações do navegador
            </button>
            <button
                type="button"
                x-on:click="notificationCenter.testAlert()"
                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
            >
                Testar som e push
            </button>
        </div>
    </div>
</div>
