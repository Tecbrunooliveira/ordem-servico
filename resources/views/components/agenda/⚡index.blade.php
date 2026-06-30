<?php

use App\Enums\OrdemServicoTipo;
use App\Support\AgendaRepository;
use App\Support\OrdemServicoRepository;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

new class extends Component
{
    use WireUiActions;

    public ?array $selectedEvent = null;

    /** @var array<int, array<string, mixed>> */
    public array $ordens = [];

    public function mount(): void
    {
        $this->carregarOrdens();
    }

    private function carregarOrdens(): void
    {
        $this->ordens = AgendaRepository::ordensParaAgenda();
    }

    public function events(): array
    {
        return collect($this->ordens)
            ->sortBy(fn (array $ordem) => OrdemServicoRepository::agendamentoDatetime(
                $ordem['data_agendada'],
                $ordem['hora_agendada'] ?? null,
            ) ?? '9999-12-31')
            ->map(function (array $ordem) {
                $hora = $ordem['hora_agendada'] ?? null;

                return [
                    'id' => (string) $ordem['id'],
                    'title' => $ordem['titulo'],
                    'start' => OrdemServicoRepository::agendamentoDatetime($ordem['data_agendada'], $hora),
                    'allDay' => blank($hora),
                    'backgroundColor' => $ordem['tipoColor'],
                    'borderColor' => $ordem['tipoColor'],
                    'extendedProps' => [
                        'cliente' => $ordem['cliente'],
                        'tipo' => $ordem['tipoLabel'],
                        'tipoColor' => $ordem['tipoColor'],
                        'status' => $ordem['status'],
                        'descricao' => $ordem['descricao'],
                        'data' => OrdemServicoRepository::formatAgendamento($ordem['data_agendada'], $hora),
                    ],
                ];
            })
            ->values()
            ->all();
    }

    public function reschedule(int $id, string $date): void
    {
        AgendaRepository::reagendar($id, $date);
        $this->carregarOrdens();

        $this->notification()->success('Data atualizada', 'A ordem de serviço foi reagendada.');
        $this->dispatch('calendar-refresh');
    }

    public function showEvent(int $id): void
    {
        $this->selectedEvent = AgendaRepository::eventoDetalhe($id);
    }

    public function closeEvent(): void
    {
        $this->selectedEvent = null;
    }

    public function with(): array
    {
        return [
            'legend' => collect(OrdemServicoTipo::cases())
                ->map(fn ($tipo) => ['label' => $tipo->label(), 'color' => $tipo->color()])
                ->all(),
            'upcoming' => collect($this->ordens)
                ->filter(fn (array $ordem) => $ordem['data_agendada'] >= now()->toDateString())
                ->sortBy(fn (array $ordem) => OrdemServicoRepository::agendamentoDatetime(
                    $ordem['data_agendada'],
                    $ordem['hora_agendada'] ?? null,
                ) ?? '9999-12-31')
                ->take(5)
                ->values()
                ->all(),
        ];
    }
};
?>

<div>
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Calendário de Atividades</h2>
            <p class="text-sm text-slate-500">Arraste os eventos para reagendar. Clique para ver detalhes.</p>
        </div>

        <div class="flex flex-wrap gap-3">
            @foreach ($legend as $item)
                <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600 shadow-sm ring-1 ring-slate-200">
                    <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $item['color'] }}"></span>
                    {{ $item['label'] }}
                </span>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-4">
        <div class="xl:col-span-3">
            <div
                wire:ignore
                x-data="agendaCalendar(@js($this->events()))"
                x-init="init()"
                @calendar-refresh.window="refreshEvents()"
                class="agenda-calendar overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6"
            >
                <div x-ref="calendar" class="min-h-[36rem] w-full"></div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-4 font-semibold text-slate-900">Próximas atividades</h3>
                <div class="space-y-3">
                    @forelse ($upcoming as $ordem)
                        <button
                            type="button"
                            wire:click="showEvent({{ $ordem['id'] }})"
                            class="w-full rounded-lg border border-slate-100 p-3 text-left transition hover:border-brand-200 hover:bg-brand-50/50"
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    class="mt-0.5 h-2.5 w-2.5 shrink-0 rounded-full"
                                    style="background-color: {{ $ordem['tipoColor'] }}"
                                ></div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-900">{{ $ordem['titulo'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $ordem['cliente'] }}</p>
                                    <p class="mt-1 text-xs font-medium text-brand-600">
                                        {{ OrdemServicoRepository::formatAgendamento($ordem['data_agendada'], $ordem['hora_agendada'] ?? null) }}
                                    </p>
                                </div>
                            </div>
                        </button>
                    @empty
                        <p class="text-sm text-slate-500">Nenhuma atividade agendada.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-2 font-semibold text-slate-900">Dicas</h3>
                <ul class="space-y-2 text-xs text-slate-500">
                    <li>• Clique em um evento para ver detalhes</li>
                    <li>• Arraste um evento para outra data</li>
                    <li>• Clique em um dia vazio para criar ordem</li>
                </ul>
                <div class="mt-4">
                    <x-button primary flat label="Nova ordem de serviço" href="{{ route('ordens-servico.index') }}" icon="plus" />
                </div>
            </div>
        </div>
    </div>

    @if ($selectedEvent)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
            wire:click.self="closeEvent"
        >
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <span
                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium text-white"
                            style="background-color: {{ $selectedEvent['tipoColor'] }}"
                        >
                            {{ $selectedEvent['tipo'] }}
                        </span>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">{{ $selectedEvent['titulo'] }}</h3>
                    </div>
                    <button type="button" wire:click="closeEvent" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                        <x-icon name="x-mark" class="h-5 w-5" />
                    </button>
                </div>

                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Cliente</dt>
                        <dd class="font-medium text-slate-900">{{ $selectedEvent['cliente'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Data</dt>
                        <dd class="font-medium text-slate-900">{{ $selectedEvent['data'] ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Status</dt>
                        <dd class="font-medium text-slate-900">{{ $selectedEvent['status'] }}</dd>
                    </div>
                    @if ($selectedEvent['descricao'])
                        <div>
                            <dt class="mb-1 text-slate-500">Descrição</dt>
                            <dd class="text-slate-700">{{ $selectedEvent['descricao'] }}</dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
                    <x-button flat label="Fechar" wire:click="closeEvent" />
                    <x-button primary label="Editar ordem" href="{{ route('ordens-servico.index') }}" />
                </div>
            </div>
        </div>
    @endif
</div>
