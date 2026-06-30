<?php

use App\Support\DashboardRepository;
use Livewire\Component;

new class extends Component
{
    public function with(): array
    {
        return [
            'stats' => DashboardRepository::stats(),
            'agenda' => DashboardRepository::agendaHoje(),
            'notifications' => DashboardRepository::notificacoes(),
        ];
    }
};
?>

<div class="space-y-6">
    {{-- Cards de resumo --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($stats as $stat)
            <x-dashboard.stat-card
                :value="$stat['value']"
                :label="$stat['label']"
                :hint="$stat['hint']"
                :hint-color="$stat['hintColor']"
                :icon="$stat['icon']"
                :icon-bg="$stat['iconBg']"
                :icon-color="$stat['iconColor']"
            />
        @endforeach
    </div>

    {{-- Agenda + Avisos lado a lado --}}
    <div class="dashboard-panels grid grid-cols-1 gap-6 lg:grid-cols-2 lg:items-start">
        {{-- Agenda de Hoje --}}
        <div class="flex min-h-0 flex-col rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-slate-900">Agenda de Hoje</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($agenda as $item)
                    <div class="flex gap-4 px-5 py-4">
                        <div class="shrink-0 text-center">
                            <span class="text-sm font-bold text-brand-600">{{ $item['time'] }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-900">{{ $item['title'] }}</p>
                            <p class="text-xs text-slate-500">{{ $item['description'] }}</p>
                            <div class="mt-2 flex flex-wrap gap-3 text-xs text-slate-400">
                                <span class="inline-flex items-center gap-1">
                                    <x-icon name="user" class="h-3.5 w-3.5" />
                                    {{ $item['person'] }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <x-icon name="map-pin" class="h-3.5 w-3.5" />
                                    {{ $item['location'] }}
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-slate-600">
                        Nenhuma ordem de serviço agendada para hoje.
                    </div>
                @endforelse
            </div>
            <div class="border-t border-slate-100 px-5 py-3">
                <a href="{{ route('agenda.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">Ver todas as atividades →</a>
            </div>
        </div>

        {{-- Avisos e Notificações --}}
        <div class="flex min-h-0 flex-col rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-slate-900">Avisos e Notificações</h2>
                <a href="{{ route('dashboard') }}" class="text-xs font-medium text-brand-600 hover:text-brand-700">Ver no painel →</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($notifications as $notification)
                    @php
                        $styles = match ($notification['type']) {
                            'warning' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-600', 'icon' => 'exclamation-triangle'],
                            'info' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'icon' => 'information-circle'],
                            'success' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-600', 'icon' => 'check-circle'],
                            default => ['bg' => 'bg-slate-100', 'text' => 'text-slate-600', 'icon' => 'bell'],
                        };
                    @endphp
                    <div class="flex gap-3 px-5 py-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $styles['bg'] }} {{ $styles['text'] }}">
                            <x-icon :name="$styles['icon']" class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-medium text-slate-900">{{ $notification['title'] }}</p>
                                <span class="shrink-0 text-xs text-slate-400">{{ $notification['time'] }}</span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $notification['message'] }}</p>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-slate-600">
                        Nenhum aviso no momento.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
