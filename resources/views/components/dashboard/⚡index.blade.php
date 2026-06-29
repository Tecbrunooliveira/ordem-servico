<?php

use Livewire\Component;

new class extends Component
{
    public array $stats = [
        ['value' => '12', 'label' => 'Tarefas Hoje', 'hint' => '3 em atraso', 'hintColor' => 'text-red-500', 'icon' => 'clipboard-document-list', 'iconBg' => 'bg-blue-100', 'iconColor' => 'text-blue-600'],
        ['value' => '5', 'label' => 'Ordem Serviço', 'hint' => '3 hoje', 'hintColor' => 'text-emerald-600', 'icon' => 'document-text', 'iconBg' => 'bg-emerald-100', 'iconColor' => 'text-emerald-600'],
        // ['value' => '18', 'label' => 'Projetos Ativos', 'hint' => '4 em risco', 'hintColor' => 'text-orange-500', 'icon' => 'folder', 'iconBg' => 'bg-orange-100', 'iconColor' => 'text-orange-600'],
    ];

    public array $agenda = [
        ['time' => '08:00', 'title' => 'Visita Técnica - Cliente ABC', 'description' => 'Manutenção preventiva', 'person' => 'Carlos Mendes', 'location' => 'São Paulo, SP'],
        ['time' => '10:30', 'title' => 'Treinamento - Segurança', 'description' => 'NR-10 atualização', 'person' => 'Ana Paula', 'location' => 'Sala 3'],
        ['time' => '14:00', 'title' => 'Reunião de Projeto Alpha', 'description' => 'Alinhamento de entregas', 'person' => 'Equipe Técnica', 'location' => 'Online'],
        ['time' => '16:30', 'title' => 'Inspeção - Obra Delta', 'description' => 'Verificação de instalações', 'person' => 'Roberto Lima', 'location' => 'Campinas, SP'],
    ];

    // public array $featuredProjects = [
    //     ['name' => 'Projeto Alpha', 'progress' => 75, 'status' => 'Em andamento', 'statusColor' => 'blue'],
    //     ['name' => 'Projeto Beta', 'progress' => 45, 'status' => 'Em andamento', 'statusColor' => 'blue'],
    //     ['name' => 'Projeto Gama', 'progress' => 90, 'status' => 'Em andamento', 'statusColor' => 'blue'],
    //     ['name' => 'Projeto Delta', 'progress' => 30, 'status' => 'Em risco', 'statusColor' => 'orange'],
    // ];

    // public array $trainings = [
    //     ['day' => '24', 'month' => 'MAI', 'title' => 'NR-35 - Trabalho em Altura', 'time' => '09:00 - 12:00', 'room' => 'Sala 2'],
    //     ['day' => '28', 'month' => 'MAI', 'title' => 'Primeiros Socorros', 'time' => '14:00 - 17:00', 'room' => 'Auditório'],
    //     ['day' => '02', 'month' => 'JUN', 'title' => 'Gestão de Projetos', 'time' => '08:30 - 11:30', 'room' => 'Sala 1'],
    // ];

    public array $notifications = [
        ['type' => 'warning', 'title' => 'Tarefa em atraso', 'message' => 'A tarefa "Relatório mensal" está 2 dias em atraso.', 'time' => 'há 1h'],
        ['type' => 'info', 'title' => 'Novo projeto', 'message' => 'O projeto "Epsilon" foi criado e aguarda alocação.', 'time' => 'há 3h'],
        ['type' => 'success', 'title' => 'Visita concluída', 'message' => 'Visita técnica no Cliente XYZ finalizada com sucesso.', 'time' => 'há 5h'],
        ['type' => 'warning', 'title' => 'Treinamento próximo', 'message' => 'NR-35 inicia amanhã às 09:00.', 'time' => 'há 1d'],
    ];
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

    {{-- Linha do meio --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        {{-- Agenda de Hoje --}}
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-slate-900">Agenda de Hoje</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach ($agenda as $item)
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
                @endforeach
            </div>
            <div class="border-t border-slate-100 px-5 py-3">
                <a href="#" class="text-sm font-medium text-brand-600 hover:text-brand-700">Ver todas as atividades →</a>
            </div>
        </div>

        {{-- Projetos em Destaque --}}
        {{--
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            ...
        </div>
        --}}

        {{-- Avisos e Notificações --}}
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-slate-900">Avisos e Notificações</h2>
                <a href="#" class="text-xs font-medium text-brand-600 hover:text-brand-700">Ver todas →</a>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach ($notifications as $notification)
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
                @endforeach
            </div>
        </div>
    </div>

    {{-- Linha inferior: Próximos Treinamentos (oculto)
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-slate-900">Próximos Treinamentos</h2>
                <a href="#" class="text-xs font-medium text-brand-600 hover:text-brand-700">Ver todos</a>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach ($trainings as $training)
                    ...
                @endforeach
            </div>
        </div>
    </div>
    --}}
</div>
