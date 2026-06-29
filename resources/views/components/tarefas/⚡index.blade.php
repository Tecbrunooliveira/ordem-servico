<?php

use App\Enums\TarefaCategoria;
use App\Enums\TarefaPrioridade;
use App\Enums\TarefaRecorrencia;
use App\Enums\TarefaStatus;
use App\Support\TarefaRepository;
use Livewire\Component;
use Livewire\WithFileUploads;
use WireUi\Traits\WireUiActions;

new class extends Component
{
    use WireUiActions;
    use WithFileUploads;

    public string $viewMode = 'lista';

    public bool $showForm = false;

    public string $busca = '';

    public string $filtroPrioridade = '';

    public string $filtroResponsavel = '';

    public string $filtroStatus = '';

    public string $filtroVencimento = '';

    public ?int $editingId = null;

    public int $nextId = 9;

    /** @var array<int, array<string, mixed>> */
    public array $tarefas = [];

    public string $titulo = '';

    public string $descricao = '';

    public string $status = 'pendente';

    public string $prioridade = 'media';

    public string $data_vencimento = '';

    public string $responsavel = '';

    public string $categoria = 'operacional';

    public string $data_inicio = '';

    public int $formTempoSegundos = 0;

    public bool $timerRunning = false;

    public ?int $timerStartedAt = null;

    public string $recorrencia = 'nenhuma';

    /** @var array<int, array{nome: string, tamanho: string}> */
    public array $formAnexos = [];

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $novosAnexos = [];

    public ?int $runningTaskId = null;

    public ?int $runningTaskStartedAt = null;

    public bool $showAjusteRapido = false;

    public ?int $ajusteRapidoId = null;

    public string $ajusteRapidoCampo = '';

    public string $ajusteRapidoValor = '';

    public string $ajusteRapidoTitulo = '';

    public bool $showVisualizar = false;

    /** @var array<string, mixed>|null */
    public ?array $visualizarTarefa = null;

    public string $novoComentario = '';

    public int $nextComentarioId = 10;

    public bool $showComentarioModal = false;

    /** @var array<string, mixed>|null */
    public ?array $comentarioTarefa = null;

    /** @var array<int, string> */
    public array $responsaveis = [];

    public function mount(): void
    {
        $this->responsaveis = TarefaRepository::responsaveisDisponiveis();
        $this->carregarTarefas();
    }

    private function carregarTarefas(): void
    {
        $this->tarefas = TarefaRepository::allAsArrays();
    }

    private function persistTarefaIndex(int $index): void
    {
        TarefaRepository::persistFromArray($this->tarefas[$index]);
        $this->tarefas[$index] = TarefaRepository::findAsArray($this->tarefas[$index]['id']);
    }
    protected function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:'.implode(',', array_column(TarefaStatus::cases(), 'value'))],
            'prioridade' => ['required', 'in:'.implode(',', array_column(TarefaPrioridade::cases(), 'value'))],
            'data_vencimento' => ['nullable', 'date'],
            'responsavel' => ['required', 'string', 'max:255'],
            'categoria' => ['required', 'in:'.implode(',', array_column(TarefaCategoria::cases(), 'value'))],
            'data_inicio' => ['required', 'date'],
            'recorrencia' => ['required', 'in:'.implode(',', array_column(TarefaRecorrencia::cases(), 'value'))],
        ];
    }

    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['lista', 'kanban', 'calendario'], true)) {
            $this->viewMode = $mode;
        }
    }

    public function create(): void
    {
        $this->closeVisualizar();
        $this->resetForm();
        $this->data_inicio = now()->toDateString();
        $this->showForm = true;
    }

    public function createWithDate(string $date): void
    {
        $this->create();
        $this->data_vencimento = $date;
    }

    public function edit(int $id): void
    {
        $this->closeVisualizar();
        $tarefa = $this->findTask($id);

        $this->editingId = $tarefa['id'];
        $this->titulo = $tarefa['titulo'];
        $this->descricao = $tarefa['descricao'];
        $this->status = $tarefa['status'];
        $this->prioridade = $tarefa['prioridade'];
        $this->data_vencimento = $tarefa['data_vencimento'] ?? '';
        $this->responsavel = $tarefa['responsavel'];
        $this->categoria = $tarefa['categoria'];
        $this->data_inicio = $tarefa['data_inicio'];
        $this->formTempoSegundos = $tarefa['tempo_segundos'];
        $this->recorrencia = $tarefa['recorrencia'];
        $this->formAnexos = $tarefa['anexos'];
        $this->timerRunning = false;
        $this->timerStartedAt = null;
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['data_vencimento'] = $data['data_vencimento'] ?: null;

        if ($this->timerRunning && $this->timerStartedAt) {
            $this->formTempoSegundos += now()->timestamp - $this->timerStartedAt;
            $this->timerRunning = false;
            $this->timerStartedAt = null;
        }

        $payload = [
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'],
            'status' => $data['status'],
            'prioridade' => $data['prioridade'],
            'data_vencimento' => $data['data_vencimento'],
            'responsavel' => $data['responsavel'],
            'categoria' => $data['categoria'],
            'data_inicio' => $data['data_inicio'],
            'tempo_segundos' => $this->formTempoSegundos,
            'recorrencia' => $data['recorrencia'],
        ];

        if ($this->editingId) {
            TarefaRepository::updateFromForm($this->editingId, $payload);
            $tarefaId = $this->editingId;
            $this->notification()->success('Tarefa atualizada', 'As alterações foram salvas.');
        } else {
            $tarefaId = TarefaRepository::createFromForm($payload);
            $this->notification()->success('Tarefa criada', 'A tarefa foi adicionada com sucesso.');
        }

        foreach ($this->novosAnexos as $arquivo) {
            TarefaRepository::storeAnexo($tarefaId, $arquivo);
        }

        $this->carregarTarefas();
        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('tarefas-updated');
    }

    public function cancel(): void
    {
        if ($this->timerRunning) {
            $this->timerRunning = false;
            $this->timerStartedAt = null;
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(int $id): void
    {
        TarefaRepository::delete($id);
        $this->carregarTarefas();

        if ($this->runningTaskId === $id) {
            $this->runningTaskId = null;
            $this->runningTaskStartedAt = null;
        }

        $this->notification()->success('Tarefa removida', 'A tarefa foi excluída.');
        $this->dispatch('tarefas-updated');
    }

    public function updateStatus(int $id, string $status): void
    {
        if (! in_array($status, array_column(TarefaStatus::cases(), 'value'), true)) {
            return;
        }

        $index = $this->findTaskIndex($id);
        $this->tarefas[$index]['status'] = $status;
        $this->persistTarefaIndex($index);
        $this->notification()->send([
            'icon' => 'success',
            'title' => 'Status atualizado',
            'timeout' => 3000,
        ]);
        $this->dispatch('tarefas-updated');
    }

    public function updatePrioridade(int $id, string $prioridade): void
    {
        if (! in_array($prioridade, array_column(TarefaPrioridade::cases(), 'value'), true)) {
            return;
        }

        $index = $this->findTaskIndex($id);
        $this->tarefas[$index]['prioridade'] = $prioridade;
        $this->persistTarefaIndex($index);
        $this->notification()->send([
            'icon' => 'success',
            'title' => 'Prioridade atualizada',
            'timeout' => 3000,
        ]);
        $this->dispatch('tarefas-updated');
    }

    public function updateVencimento(int $id, string $date): void
    {
        $index = $this->findTaskIndex($id);
        $this->tarefas[$index]['data_vencimento'] = $date ?: null;
        $this->persistTarefaIndex($index);
        $this->notification()->send([
            'icon' => 'success',
            'title' => 'Vencimento atualizado',
            'timeout' => 3000,
        ]);
        $this->dispatch('tarefas-updated');
    }

    public function updateResponsavel(int $id, string $responsavel): void
    {
        if (! in_array($responsavel, $this->responsaveis, true)) {
            return;
        }

        $index = $this->findTaskIndex($id);
        $this->tarefas[$index]['responsavel'] = $responsavel;
        $this->persistTarefaIndex($index);
        $this->notification()->send([
            'icon' => 'success',
            'title' => 'Responsável atualizado',
            'timeout' => 3000,
        ]);
        $this->dispatch('tarefas-updated');
    }

    public function openAjusteRapido(int $id, string $campo): void
    {
        if (! in_array($campo, ['vencimento', 'status', 'prioridade', 'responsavel'], true)) {
            return;
        }

        $tarefa = $this->findTask($id);

        $this->ajusteRapidoId = $id;
        $this->ajusteRapidoCampo = $campo;
        $this->ajusteRapidoTitulo = $tarefa['titulo'];
        $this->ajusteRapidoValor = match ($campo) {
            'vencimento' => $tarefa['data_vencimento'] ?? '',
            'status' => $tarefa['status'],
            'prioridade' => $tarefa['prioridade'],
            'responsavel' => $tarefa['responsavel'],
            default => '',
        };
        $this->showAjusteRapido = true;
    }

    public function saveAjusteRapido(): void
    {
        if (! $this->ajusteRapidoId || ! $this->ajusteRapidoCampo) {
            return;
        }

        match ($this->ajusteRapidoCampo) {
            'vencimento' => $this->updateVencimento($this->ajusteRapidoId, $this->ajusteRapidoValor),
            'status' => $this->updateStatus($this->ajusteRapidoId, $this->ajusteRapidoValor),
            'prioridade' => $this->updatePrioridade($this->ajusteRapidoId, $this->ajusteRapidoValor),
            'responsavel' => $this->updateResponsavel($this->ajusteRapidoId, $this->ajusteRapidoValor),
            default => null,
        };

        $this->closeAjusteRapido();
    }

    public function closeAjusteRapido(): void
    {
        $this->showAjusteRapido = false;
        $this->ajusteRapidoId = null;
        $this->ajusteRapidoCampo = '';
        $this->ajusteRapidoValor = '';
        $this->ajusteRapidoTitulo = '';
    }

    public function visualizar(int $id): void
    {
        $this->showForm = false;
        $this->novoComentario = '';
        $this->visualizarTarefa = $this->findTask($id);

        if (! isset($this->visualizarTarefa['comentarios'])) {
            $index = $this->findTaskIndex($id);
            $this->tarefas[$index]['comentarios'] = [];
            $this->visualizarTarefa = $this->tarefas[$index];
        }

        $this->showVisualizar = true;
    }

    public function adicionarComentario(): void
    {
        $tarefaRef = $this->visualizarTarefa ?? $this->comentarioTarefa;

        if (! $tarefaRef) {
            return;
        }

        $texto = trim($this->novoComentario);

        if ($texto === '') {
            $this->notification()->send([
                'icon' => 'warning',
                'title' => 'Comentário vazio',
                'description' => 'Digite uma mensagem antes de enviar.',
                'timeout' => 3000,
            ]);

            return;
        }

        $index = $this->findTaskIndex($tarefaRef['id']);
        TarefaRepository::addComentario($tarefaRef['id'], $texto);
        $this->tarefas[$index] = TarefaRepository::findAsArray($tarefaRef['id']);

        if ($this->visualizarTarefa && $this->visualizarTarefa['id'] === $tarefaRef['id']) {
            $this->visualizarTarefa = $this->tarefas[$index];
        }

        if ($this->comentarioTarefa && $this->comentarioTarefa['id'] === $tarefaRef['id']) {
            $this->comentarioTarefa = $this->tarefas[$index];
        }

        $this->novoComentario = '';

        $this->notification()->send([
            'icon' => 'success',
            'title' => 'Comentário adicionado',
            'timeout' => 3000,
        ]);
    }

    public function openComentarioModal(int $id): void
    {
        $tarefa = $this->findTask($id);

        if (! isset($tarefa['comentarios'])) {
            $index = $this->findTaskIndex($id);
            $this->tarefas[$index]['comentarios'] = [];
            $tarefa = $this->tarefas[$index];
        }

        $this->comentarioTarefa = $tarefa;
        $this->novoComentario = '';
        $this->showComentarioModal = true;
    }

    public function closeComentarioModal(): void
    {
        $this->showComentarioModal = false;
        $this->comentarioTarefa = null;
        $this->novoComentario = '';
    }

    public function closeVisualizar(): void
    {
        $this->showVisualizar = false;
        $this->novoComentario = '';
    }

    public function toggleFormTimer(): void
    {
        if ($this->timerRunning) {
            $this->formTempoSegundos += now()->timestamp - (int) $this->timerStartedAt;
            $this->timerRunning = false;
            $this->timerStartedAt = null;
        } else {
            $this->timerRunning = true;
            $this->timerStartedAt = now()->timestamp;
        }
    }

    public function toggleTaskTimer(int $id): void
    {
        if ($this->runningTaskId === $id) {
            $index = $this->findTaskIndex($id);
            $this->tarefas[$index]['tempo_segundos'] += now()->timestamp - (int) $this->runningTaskStartedAt;
            $this->persistTarefaIndex($index);
            $this->runningTaskId = null;
            $this->runningTaskStartedAt = null;
        } else {
            if ($this->runningTaskId) {
                $index = $this->findTaskIndex($this->runningTaskId);
                $this->tarefas[$index]['tempo_segundos'] += now()->timestamp - (int) $this->runningTaskStartedAt;
                $this->persistTarefaIndex($index);
            }

            $this->runningTaskId = $id;
            $this->runningTaskStartedAt = now()->timestamp;
        }
    }

    public function updatedNovosAnexos(): void
    {
        $this->validateOnly('novosAnexos.*', [
            'novosAnexos.*' => ['file', 'max:5120'],
        ]);
    }

    public function removeAnexo(int $index): void
    {
        $anexo = $this->formAnexos[$index] ?? null;

        if (isset($anexo['id'])) {
            TarefaRepository::deleteAnexo((int) $anexo['id']);
            $this->carregarTarefas();

            if ($this->editingId) {
                $tarefa = collect($this->tarefas)->firstWhere('id', $this->editingId);
                $this->formAnexos = $tarefa['anexos'] ?? [];
            }
        }

        unset($this->formAnexos[$index]);
        $this->formAnexos = array_values($this->formAnexos);
    }

    public function calendarEvents(): array
    {
        return collect($this->tarefas)
            ->filter(fn (array $tarefa) => ! empty($tarefa['data_vencimento']))
            ->map(function (array $tarefa) {
                $prioridade = TarefaPrioridade::from($tarefa['prioridade']);

                return [
                    'id' => (string) $tarefa['id'],
                    'title' => $tarefa['titulo'],
                    'start' => $tarefa['data_vencimento'],
                    'allDay' => true,
                    'backgroundColor' => $prioridade->color(),
                    'borderColor' => $prioridade->color(),
                    'extendedProps' => [
                        'responsavel' => $tarefa['responsavel'],
                        'status' => TarefaStatus::from($tarefa['status'])->label(),
                        'prioridade' => $prioridade->label(),
                    ],
                ];
            })
            ->values()
            ->all();
    }

    public function tempoAtual(int $segundos, ?int $taskId = null): string
    {
        $total = $segundos;

        if ($taskId && $this->runningTaskId === $taskId && $this->runningTaskStartedAt) {
            $total += now()->timestamp - $this->runningTaskStartedAt;
        }

        return $this->formatTempo($total);
    }

    public function tempoFormAtual(): string
    {
        $total = $this->formTempoSegundos;

        if ($this->timerRunning && $this->timerStartedAt) {
            $total += now()->timestamp - $this->timerStartedAt;
        }

        return $this->formatTempo($total);
    }

    private function findTask(int $id): array
    {
        return collect($this->tarefas)->firstWhere('id', $id) ?? throw new \RuntimeException('Tarefa não encontrada.');
    }

    private function findTaskIndex(int $id): int
    {
        $index = collect($this->tarefas)->search(fn (array $tarefa) => $tarefa['id'] === $id);

        if ($index === false) {
            throw new \RuntimeException('Tarefa não encontrada.');
        }

        return $index;
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId',
            'titulo',
            'descricao',
            'data_vencimento',
            'responsavel',
        ]);
        $this->status = TarefaStatus::Pendente->value;
        $this->prioridade = TarefaPrioridade::Media->value;
        $this->categoria = TarefaCategoria::Operacional->value;
        $this->recorrencia = TarefaRecorrencia::Nenhuma->value;
        $this->data_inicio = now()->toDateString();
        $this->formTempoSegundos = 0;
        $this->timerRunning = false;
        $this->timerStartedAt = null;
        $this->formAnexos = [];
        $this->novosAnexos = [];
        $this->resetValidation();
    }

    private function formatTempo(int $segundos): string
    {
        $horas = intdiv($segundos, 3600);
        $minutos = intdiv($segundos % 3600, 60);
        $seg = $segundos % 60;

        return sprintf('%02d:%02d:%02d', $horas, $minutos, $seg);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024).' KB';
        }

        return $bytes.' B';
    }

    /** @return array<int, array<string, mixed>> */
    private function tarefasFiltradas(): array
    {
        $busca = mb_strtolower(trim($this->busca));

        return collect($this->tarefas)
            ->filter(function (array $tarefa) use ($busca): bool {
                if ($busca !== '' && ! str_contains(mb_strtolower($tarefa['titulo']), $busca)) {
                    return false;
                }

                if ($this->filtroPrioridade !== '' && $tarefa['prioridade'] !== $this->filtroPrioridade) {
                    return false;
                }

                if ($this->filtroResponsavel !== '' && $tarefa['responsavel'] !== $this->filtroResponsavel) {
                    return false;
                }

                if ($this->filtroStatus !== '' && $tarefa['status'] !== $this->filtroStatus) {
                    return false;
                }

                if ($this->filtroVencimento !== '' && ($tarefa['data_vencimento'] ?? '') !== $this->filtroVencimento) {
                    return false;
                }

                return true;
            })
            ->values()
            ->all();
    }

    public function limparFiltros(): void
    {
        $this->reset([
            'busca',
            'filtroPrioridade',
            'filtroResponsavel',
            'filtroStatus',
            'filtroVencimento',
        ]);
    }

    public function with(): array
    {
        return [
            'statuses' => TarefaStatus::cases(),
            'prioridades' => TarefaPrioridade::options(),
            'categorias' => TarefaCategoria::options(),
            'recorrencias' => TarefaRecorrencia::options(),
            'pendentesCount' => collect($this->tarefas)->whereIn('status', ['pendente', 'em_andamento'])->count(),
            'tarefasLista' => $this->tarefasFiltradas(),
            'totalTarefas' => count($this->tarefas),
        ];
    }
};
?>

<div>
    @if ($runningTaskId || $timerRunning)
        <div wire:poll.1s></div>
    @endif

    @if ($showForm)
        <div class="mx-auto max-w-4xl">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">
                        {{ $editingId ? 'Editar Tarefa' : 'Nova Tarefa' }}
                    </h2>
                    <p class="text-sm text-slate-600">Preencha os dados da tarefa e controle o tempo gasto.</p>
                </div>
                <x-button flat label="Voltar" icon="arrow-left" wire:click="cancel" />
            </div>

            <form wire:submit="save" class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-input wire:model="titulo" label="Título" placeholder="Ex: Revisar relatório mensal" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-textarea wire:model="descricao" label="Descrição" placeholder="Detalhes da tarefa..." />
                    </div>

                    <x-native-select wire:model="status" label="Status">
                        @foreach ($statuses as $item)
                            <option value="{{ $item->value }}">{{ $item->label() }}</option>
                        @endforeach
                    </x-native-select>

                    <x-native-select wire:model="prioridade" label="Prioridade">
                        @foreach ($prioridades as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-native-select>

                    <x-input wire:model="data_vencimento" label="Data vencimento" type="date" />

                    <x-native-select wire:model="responsavel" label="Responsável">
                        <option value="">Selecione</option>
                        @foreach ($responsaveis as $nome)
                            <option value="{{ $nome }}">{{ $nome }}</option>
                        @endforeach
                    </x-native-select>

                    <x-native-select wire:model="categoria" label="Categoria">
                        @foreach ($categorias as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-native-select>

                    <x-input wire:model="data_inicio" label="Data início" type="date" />

                    <x-native-select wire:model="recorrencia" label="Recorrência">
                        @foreach ($recorrencias as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-native-select>

                    <div class="sm:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-gray-700">Tempo gasto</label>
                        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <span class="font-mono text-2xl font-semibold text-slate-900">{{ $this->tempoFormAtual() }}</span>
                            <div class="flex gap-2">
                                @if (! $timerRunning)
                                    <button type="button" wire:click="toggleFormTimer" class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-emerald-500 text-white shadow-sm hover:bg-emerald-600">
                                        <x-icon name="play" class="h-4 w-4" />
                                    </button>
                                @else
                                    <button type="button" wire:click="toggleFormTimer" class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-red-500 text-white shadow-sm hover:bg-red-600">
                                        <x-icon name="stop" class="h-4 w-4" />
                                    </button>
                                @endif
                            </div>
                            <span class="text-xs text-slate-600">Use play/stop para registrar o tempo</span>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-gray-700">Anexos</label>
                        <div class="space-y-3 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
                            <input
                                type="file"
                                wire:model="novosAnexos"
                                multiple
                                class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-500 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-brand-600"
                            >
                            @if (count($formAnexos))
                                <ul class="divide-y divide-slate-200 rounded-lg border border-slate-200 bg-white">
                                    @foreach ($formAnexos as $index => $anexo)
                                        <li class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                                            <div class="flex min-w-0 items-center gap-2">
                                                <x-icon name="paper-clip" class="h-4 w-4 shrink-0 text-slate-400" />
                                                <span class="truncate text-slate-700">{{ $anexo['nome'] }}</span>
                                                <span class="shrink-0 text-xs text-slate-400">({{ $anexo['tamanho'] }})</span>
                                            </div>
                                            <button type="button" wire:click="removeAnexo({{ $index }})" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-600 ring-1 ring-red-200 hover:bg-red-50">
                                                <x-icon name="x-mark" class="h-4 w-4" />
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                    <x-button flat label="Cancelar" wire:click="cancel" />
                    <x-button primary type="submit" label="{{ $editingId ? 'Salvar alterações' : 'Cadastrar tarefa' }}" />
                </div>
            </form>
        </div>
    @else
        <div
            class="mb-4 space-y-4"
            x-data="{ filtrosAbertos: false }"
        >
            <div class="page-list-toolbar">
                <div class="page-list-toolbar-filters">
                    <div class="min-w-0 flex-1">
                        <x-input
                            wire:model.live.debounce.300ms="busca"
                            icon="magnifying-glass"
                            label="Pesquisar"
                            placeholder="Título da tarefa"
                        />
                    </div>

                    <button
                        type="button"
                        x-on:click="filtrosAbertos = ! filtrosAbertos"
                        class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                    >
                        <x-icon name="plus" class="h-4 w-4 transition-transform" x-bind:class="filtrosAbertos && 'rotate-45'" />
                        Filtros
                    </button>
                </div>

                <x-button primary icon="plus" label="Nova Tarefa" wire:click="create" class="shrink-0" />
            </div>

            <div
                x-show="filtrosAbertos"
                x-transition
                x-cloak
                class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
            >
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <x-native-select wire:model.live="filtroPrioridade" label="Prioridade">
                        <option value="">Todas</option>
                        @foreach ($prioridades as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-native-select>

                    <x-native-select wire:model.live="filtroResponsavel" label="Responsável">
                        <option value="">Todos</option>
                        @foreach ($responsaveis as $nome)
                            <option value="{{ $nome }}">{{ $nome }}</option>
                        @endforeach
                    </x-native-select>

                    <x-native-select wire:model.live="filtroStatus" label="Status">
                        <option value="">Todos</option>
                        @foreach ($statuses as $item)
                            <option value="{{ $item->value }}">{{ $item->label() }}</option>
                        @endforeach
                    </x-native-select>

                    <x-input wire:model.live="filtroVencimento" label="Vencimento" type="date" />
                </div>

                @if ($busca || $filtroPrioridade || $filtroResponsavel || $filtroStatus || $filtroVencimento)
                    <div class="mt-4 flex justify-end border-t border-slate-100 pt-4">
                        <x-button flat label="Limpar filtros" wire:click="limparFiltros" />
                    </div>
                @endif
            </div>
        </div>

        {{-- Modos de visualização: Lista, Kanban e Calendário (ocultos)
        <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1 shadow-sm">
            <button type="button" wire:click="setViewMode('lista')">Lista</button>
            <button type="button" wire:click="setViewMode('kanban')">Kanban</button>
            <button type="button" wire:click="setViewMode('calendario')">Calendário</button>
        </div>
        --}}

        <div class="page-list-table rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="page-list-table-scroll overflow-x-auto md:overflow-x-visible">
                <table class="w-full text-left text-sm">
                        <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                            <tr>
                                <th class="px-5 py-3">Título</th>
                                <th class="px-5 py-3">Responsável</th>
                                <th class="px-5 py-3">Prioridade</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Vencimento</th>
                                <th class="px-5 py-3 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($tarefasLista as $tarefa)
                                @php
                                    $prioridade = \App\Enums\TarefaPrioridade::from($tarefa['prioridade']);
                                    $statusEnum = \App\Enums\TarefaStatus::from($tarefa['status']);
                                @endphp
                                <tr wire:key="tarefa-lista-{{ $tarefa['id'] }}" class="hover:bg-slate-50">
                                    <td class="px-5 py-4">
                                        <p class="font-medium text-slate-900">{{ $tarefa['titulo'] }}</p>
                                        <p class="text-xs text-slate-600">{{ \App\Enums\TarefaCategoria::from($tarefa['categoria'])->label() }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-slate-700">{{ $tarefa['responsavel'] }}</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium" style="color: {{ $prioridade->color() }}">
                                            <span class="h-2 w-2 rounded-full" style="background-color: {{ $prioridade->color() }}"></span>
                                            {{ $prioridade->label() }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span @class(['inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold', $statusEnum->badgeClass()])>
                                            {{ $statusEnum->label() }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-slate-700">
                                        {{ $tarefa['data_vencimento'] ? \Illuminate\Support\Carbon::parse($tarefa['data_vencimento'])->format('d/m/Y') : '—' }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-2">
                                            <button type="button" wire:click="visualizar({{ $tarefa['id'] }})" title="Visualizar" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 ring-1 ring-slate-200 hover:bg-slate-100 hover:text-brand-600">
                                                <x-icon name="eye" class="h-4 w-4" />
                                            </button>

                                            <button type="button" wire:click="edit({{ $tarefa['id'] }})" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 ring-1 ring-slate-200 hover:bg-slate-100 hover:text-brand-600">
                                                <x-icon name="pencil" class="h-4 w-4" />
                                            </button>

                                            <button type="button" wire:click="delete({{ $tarefa['id'] }})" wire:confirm="Deseja excluir esta tarefa?" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-600 ring-1 ring-red-200 hover:bg-red-50">
                                                <x-icon name="trash" class="h-4 w-4" />
                                            </button>

                                            <x-dropdown
                                                position="top-end"
                                                width="sm"
                                                wire:key="ajuste-rapido-{{ $tarefa['id'] }}"
                                            >
                                                <x-slot name="trigger">
                                                    <button
                                                        type="button"
                                                        title="Ajuste rápido"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 ring-1 ring-slate-200 hover:bg-slate-100 hover:text-brand-600"
                                                    >
                                                        <x-icon name="ellipsis-vertical" class="h-4 w-4" />
                                                    </button>
                                                </x-slot>

                                                <x-dropdown.header label="Ajuste rápido" />

                                                <button
                                                    type="button"
                                                    wire:click.stop="openAjusteRapido({{ $tarefa['id'] }}, 'vencimento')"
                                                    class="flex w-full cursor-pointer items-center rounded-md px-4 py-2 text-sm text-secondary-600 transition-colors duration-150 hover:bg-secondary-100 hover:text-secondary-900"
                                                >
                                                    <x-icon name="calendar" class="mr-2 h-5 w-5" />
                                                    Vencimento
                                                </button>

                                                <button
                                                    type="button"
                                                    wire:click.stop="openAjusteRapido({{ $tarefa['id'] }}, 'status')"
                                                    class="flex w-full cursor-pointer items-center rounded-md px-4 py-2 text-sm text-secondary-600 transition-colors duration-150 hover:bg-secondary-100 hover:text-secondary-900"
                                                >
                                                    <x-icon name="flag" class="mr-2 h-5 w-5" />
                                                    Status
                                                </button>

                                                <button
                                                    type="button"
                                                    wire:click.stop="openAjusteRapido({{ $tarefa['id'] }}, 'prioridade')"
                                                    class="flex w-full cursor-pointer items-center rounded-md px-4 py-2 text-sm text-secondary-600 transition-colors duration-150 hover:bg-secondary-100 hover:text-secondary-900"
                                                >
                                                    <x-icon name="signal" class="mr-2 h-5 w-5" />
                                                    Prioridade
                                                </button>

                                                <button
                                                    type="button"
                                                    wire:click.stop="openAjusteRapido({{ $tarefa['id'] }}, 'responsavel')"
                                                    class="flex w-full cursor-pointer items-center rounded-md px-4 py-2 text-sm text-secondary-600 transition-colors duration-150 hover:bg-secondary-100 hover:text-secondary-900"
                                                >
                                                    <x-icon name="user" class="mr-2 h-5 w-5" />
                                                    Responsável
                                                </button>

                                                <button
                                                    type="button"
                                                    wire:click.stop="openComentarioModal({{ $tarefa['id'] }})"
                                                    class="flex w-full cursor-pointer items-center rounded-md px-4 py-2 text-sm text-secondary-600 transition-colors duration-150 hover:bg-secondary-100 hover:text-secondary-900"
                                                >
                                                    <x-icon name="chat-bubble-left-right" class="mr-2 h-5 w-5" />
                                                    Comentário
                                                </button>
                                            </x-dropdown>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center text-slate-600">
                                        @if ($busca || $filtroPrioridade || $filtroResponsavel || $filtroStatus || $filtroVencimento)
                                            Nenhuma tarefa encontrada com os filtros aplicados.
                                        @else
                                            Nenhuma tarefa cadastrada.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="border-t border-slate-100 bg-slate-50">
                            <tr>
                                <td colspan="6" class="px-5 py-3 text-sm text-slate-600">
                                    @if ($busca || $filtroPrioridade || $filtroResponsavel || $filtroStatus || $filtroVencimento)
                                        Exibindo {{ count($tarefasLista) }} de {{ $totalTarefas }} tarefas · {{ $pendentesCount }} em aberto
                                    @else
                                        {{ $totalTarefas }} {{ $totalTarefas === 1 ? 'tarefa cadastrada' : 'tarefas cadastradas' }} · {{ $pendentesCount }} em aberto
                                    @endif
                                </td>
                            </tr>
                        </tfoot>
                    </table>
            </div>
        </div>

        {{-- Kanban (oculto)
        @elseif ($viewMode === 'kanban')
            <div
                wire:key="kanban-board-{{ count($tarefas) }}"
                x-data="tarefasKanban()"
                x-init="init()"
                @tarefas-updated.window="refresh()"
                class="grid grid-cols-1 gap-4 xl:grid-cols-4"
            >
                @foreach ($statuses as $status)
                    @php
                        $colTarefas = collect($tarefas)->where('status', $status->value)->values();
                    @endphp
                    <div class="kanban-column flex min-h-[28rem] flex-col rounded-xl border border-slate-200 bg-slate-100/70">
                        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                            <h3 class="text-sm font-semibold text-slate-800">{{ $status->label() }}</h3>
                            <span class="rounded-full bg-white px-2 py-0.5 text-xs font-medium text-slate-500">{{ $colTarefas->count() }}</span>
                        </div>
                        <div
                            data-kanban-column
                            data-status="{{ $status->value }}"
                            class="flex flex-1 flex-col gap-3 overflow-y-auto p-3 sidebar-nav"
                        >
                            @foreach ($colTarefas as $tarefa)
                                @php $prioridade = \App\Enums\TarefaPrioridade::from($tarefa['prioridade']); @endphp
                                <div
                                    data-task-id="{{ $tarefa['id'] }}"
                                    class="kanban-card cursor-grab rounded-xl border border-slate-200 bg-white p-4 shadow-sm active:cursor-grabbing"
                                >
                                    <div class="mb-2 flex items-start justify-between gap-2">
                                        <p class="text-sm font-semibold text-slate-900">{{ $tarefa['titulo'] }}</p>
                                        <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $prioridade->color() }}"></span>
                                    </div>
                                    <p class="mb-3 line-clamp-2 text-xs text-slate-500">{{ $tarefa['descricao'] ?: 'Sem descrição' }}</p>
                                    <div class="mb-3 space-y-1 text-xs text-slate-500">
                                        <p>{{ $tarefa['responsavel'] }}</p>
                                        <p>{{ $tarefa['data_vencimento'] ? \Illuminate\Support\Carbon::parse($tarefa['data_vencimento'])->format('d/m/Y') : 'Sem vencimento' }}</p>
                                    </div>
                                    <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                                        <span class="font-mono text-xs text-slate-600">{{ $this->tempoAtual($tarefa['tempo_segundos'], $tarefa['id']) }}</span>
                                        <div class="flex gap-1">
                                            @if ($runningTaskId === $tarefa['id'])
                                                <button type="button" wire:click="toggleTaskTimer({{ $tarefa['id'] }})" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-500 text-white hover:bg-red-600">
                                                    <x-icon name="stop" class="h-4 w-4" />
                                                </button>
                                            @else
                                                <button type="button" wire:click="toggleTaskTimer({{ $tarefa['id'] }})" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500 text-white hover:bg-emerald-600">
                                                    <x-icon name="play" class="h-4 w-4" />
                                                </button>
                                            @endif
                                            <button type="button" wire:click="edit({{ $tarefa['id'] }})" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 ring-1 ring-slate-200 hover:bg-slate-100 hover:text-brand-600">
                                                <x-icon name="pencil" class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        --}}
        {{-- Calendário (oculto)
        @elseif ($viewMode === 'calendario')
            <div
                wire:ignore
                x-data="tarefasCalendar(@js($this->calendarEvents()))"
                x-init="init()"
                @tarefas-updated.window="refreshEvents()"
                class="tarefas-calendar overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6"
            >
                <div x-ref="calendar" class="min-h-[36rem] w-full"></div>
            </div>
        --}}
    @endif

    @if ($visualizarTarefa)
        @php
            $tarefaView = $visualizarTarefa;
            $prioridadeView = \App\Enums\TarefaPrioridade::from($tarefaView['prioridade']);
            $statusView = \App\Enums\TarefaStatus::from($tarefaView['status']);
            $categoriaView = \App\Enums\TarefaCategoria::from($tarefaView['categoria']);
            $recorrenciaView = \App\Enums\TarefaRecorrencia::from($tarefaView['recorrencia']);
        @endphp
        <div
            wire:key="visualizar-tarefa-drawer"
            x-data="{ open: @entangle('showVisualizar').live }"
            x-cloak
            x-show="open"
            x-on:keydown.escape.window="open && $wire.closeVisualizar()"
            class="fixed inset-0 z-[90]"
            style="display: none;"
            role="dialog"
            aria-modal="true"
        >
            <button
                type="button"
                x-show="open"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0 bg-slate-900/40 backdrop-blur-[1px]"
                x-on:click="$wire.closeVisualizar()"
                aria-label="Fechar"
            ></button>

            <div
                x-show="open"
                x-transition:enter="transform transition ease-out duration-300"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in duration-200"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="absolute inset-y-0 right-0 flex w-full flex-col border-l border-slate-200 bg-white shadow-2xl lg:w-[calc(100%-16rem)]"
            >
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-slate-900">Visualizar Tarefa</h2>
                        <p class="mt-0.5 truncate text-sm text-slate-600">{{ $tarefaView['titulo'] }}</p>
                    </div>
                    <button
                        type="button"
                        wire:click="closeVisualizar"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                    >
                        <x-icon name="x-mark" class="h-4 w-4" />
                    </button>
                </div>

                <div class="flex min-h-0 flex-1 flex-col lg:flex-row">
                    <div class="min-h-0 flex-1 overflow-y-auto px-6 py-6 lg:border-r lg:border-slate-100">
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Título</label>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900">
                                {{ $tarefaView['titulo'] }}
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Descrição</label>
                            <div class="min-h-[5rem] rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm whitespace-pre-wrap text-slate-900">
                                {{ $tarefaView['descricao'] ?: '—' }}
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5">
                                <span @class(['inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold', $statusView->badgeClass()])>
                                    {{ $statusView->label() }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Prioridade</label>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5">
                                <span class="inline-flex items-center gap-1.5 text-sm font-medium" style="color: {{ $prioridadeView->color() }}">
                                    <span class="h-2 w-2 rounded-full" style="background-color: {{ $prioridadeView->color() }}"></span>
                                    {{ $prioridadeView->label() }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Data vencimento</label>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900">
                                {{ $tarefaView['data_vencimento'] ? \Illuminate\Support\Carbon::parse($tarefaView['data_vencimento'])->format('d/m/Y') : '—' }}
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Responsável</label>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900">
                                {{ $tarefaView['responsavel'] }}
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Categoria</label>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900">
                                {{ $categoriaView->label() }}
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Data início</label>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900">
                                {{ \Illuminate\Support\Carbon::parse($tarefaView['data_inicio'])->format('d/m/Y') }}
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Recorrência</label>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900">
                                {{ $recorrenciaView->label() }}
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-gray-700">Tempo gasto</label>
                            <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <span class="font-mono text-2xl font-semibold text-slate-900">{{ $this->tempoAtual($tarefaView['tempo_segundos'], $tarefaView['id']) }}</span>
                                <span class="text-xs text-slate-600">Tempo total registrado nesta tarefa</span>
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-gray-700">Anexos</label>
                            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
                                @if (count($tarefaView['anexos']))
                                    <ul class="divide-y divide-slate-200 rounded-lg border border-slate-200 bg-white">
                                        @foreach ($tarefaView['anexos'] as $anexo)
                                            <li class="flex items-center gap-3 px-4 py-3 text-sm">
                                                <x-icon name="paper-clip" class="h-4 w-4 shrink-0 text-slate-400" />
                                                <span class="truncate text-slate-700">{{ $anexo['nome'] }}</span>
                                                <span class="shrink-0 text-xs text-slate-400">({{ $anexo['tamanho'] }})</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-sm text-slate-500">Nenhum anexo vinculado a esta tarefa.</p>
                                @endif
                            </div>
                        </div>
                        </div>
                    </div>

                    <div class="flex min-h-0 w-full flex-col border-t border-slate-100 bg-slate-50/50 lg:w-1/2 lg:border-t-0">
                        <div class="shrink-0 border-b border-slate-100 px-6 py-4">
                            <h3 class="text-sm font-semibold text-slate-900">Comentários</h3>
                            <p class="text-xs text-slate-500">{{ count($tarefaView['comentarios']) }} {{ count($tarefaView['comentarios']) === 1 ? 'comentário' : 'comentários' }}</p>
                        </div>

                        <div class="min-h-0 flex-1 space-y-3 overflow-y-auto px-6 py-4">
                            @forelse (array_reverse($tarefaView['comentarios']) as $comentario)
                                <article wire:key="comentario-{{ $comentario['id'] }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <div class="mb-2 flex items-start justify-between gap-3">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-700">
                                                {{ strtoupper(substr($comentario['autor'], 0, 1).substr(strstr($comentario['autor'], ' ') ?: '', 1, 1)) }}
                                            </span>
                                            <div>
                                                <p class="text-sm font-medium text-slate-900">{{ $comentario['autor'] }}</p>
                                                <p class="text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($comentario['criado_em'])->format('d/m/Y H:i') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-sm whitespace-pre-wrap text-slate-700">{{ $comentario['texto'] }}</p>
                                </article>
                            @empty
                                <div class="flex h-full min-h-[12rem] flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white px-6 py-8 text-center">
                                    <x-icon name="chat-bubble-left-right" class="mb-3 h-8 w-8 text-slate-300" />
                                    <p class="text-sm font-medium text-slate-600">Nenhum comentário ainda</p>
                                    <p class="mt-1 text-xs text-slate-500">Seja o primeiro a registrar uma observação sobre esta tarefa.</p>
                                </div>
                            @endforelse
                        </div>

                        <div class="shrink-0 border-t border-slate-100 bg-white px-6 py-4">
                            <form wire:submit="adicionarComentario" class="space-y-3">
                                <x-textarea
                                    wire:model="novoComentario"
                                    label="Novo comentário"
                                    placeholder="Escreva um comentário..."
                                    rows="3"
                                />
                                <div class="flex justify-end">
                                    <x-button primary type="submit" icon="paper-airplane" label="Enviar comentário" />
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="flex shrink-0 justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                    <x-button flat label="Fechar" wire:click="closeVisualizar" />
                    <x-button primary icon="pencil" label="Editar tarefa" wire:click="edit({{ $tarefaView['id'] }})" />
                </div>
            </div>
        </div>
    @endif

    @if ($showAjusteRapido)
        <div
            wire:key="ajuste-rapido-modal"
            class="fixed inset-0 z-[90] flex items-center justify-center p-4 sm:p-6"
            role="dialog"
            aria-modal="true"
            x-data
            x-on:keydown.escape.window="$wire.closeAjusteRapido()"
        >
            <button
                type="button"
                class="absolute inset-0 bg-slate-900/45 backdrop-blur-[2px]"
                wire:click="closeAjusteRapido"
                aria-label="Fechar"
            ></button>

            <div class="relative w-full max-w-md overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-slate-900">
                            {{ match ($ajusteRapidoCampo) {
                                'vencimento' => 'Alterar vencimento',
                                'status' => 'Alterar status',
                                'prioridade' => 'Alterar prioridade',
                                'responsavel' => 'Alterar responsável',
                                default => 'Ajuste rápido',
                            } }}
                        </h3>
                        @if ($ajusteRapidoTitulo)
                            <p class="mt-0.5 truncate text-sm text-slate-500">{{ $ajusteRapidoTitulo }}</p>
                        @endif
                    </div>
                    <button
                        type="button"
                        wire:click="closeAjusteRapido"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                    >
                        <x-icon name="x-mark" class="h-4 w-4" />
                    </button>
                </div>

                <div class="px-5 py-5">
                    @if ($ajusteRapidoCampo === 'vencimento')
                        <x-input wire:model="ajusteRapidoValor" label="Data de vencimento" type="date" />
                    @elseif ($ajusteRapidoCampo === 'status')
                        <x-native-select wire:model="ajusteRapidoValor" label="Status">
                            @foreach ($statuses as $item)
                                <option value="{{ $item->value }}">{{ $item->label() }}</option>
                            @endforeach
                        </x-native-select>
                    @elseif ($ajusteRapidoCampo === 'prioridade')
                        <x-native-select wire:model="ajusteRapidoValor" label="Prioridade">
                            @foreach ($prioridades as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-native-select>
                    @elseif ($ajusteRapidoCampo === 'responsavel')
                        <x-native-select wire:model="ajusteRapidoValor" label="Responsável">
                            @foreach ($responsaveis as $nome)
                                <option value="{{ $nome }}">{{ $nome }}</option>
                            @endforeach
                        </x-native-select>
                    @endif
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4">
                    <x-button flat label="Cancelar" wire:click="closeAjusteRapido" />
                    <x-button primary label="Salvar" wire:click="saveAjusteRapido" />
                </div>
            </div>
        </div>
    @endif

    @if ($showComentarioModal && $comentarioTarefa)
        <div
            wire:key="comentario-modal"
            class="fixed inset-0 z-[90] flex items-center justify-center p-4 sm:p-6"
            role="dialog"
            aria-modal="true"
            x-data
            x-on:keydown.escape.window="$wire.closeComentarioModal()"
        >
            <button
                type="button"
                class="absolute inset-0 bg-slate-900/45 backdrop-blur-[2px]"
                wire:click="closeComentarioModal"
                aria-label="Fechar"
            ></button>

            <div class="relative flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-slate-900">Comentários</h3>
                        <p class="mt-0.5 truncate text-sm text-slate-500">{{ $comentarioTarefa['titulo'] }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ count($comentarioTarefa['comentarios']) }} {{ count($comentarioTarefa['comentarios']) === 1 ? 'comentário' : 'comentários' }}</p>
                    </div>
                    <button
                        type="button"
                        wire:click="closeComentarioModal"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                    >
                        <x-icon name="x-mark" class="h-4 w-4" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 space-y-3 overflow-y-auto px-5 py-4">
                    @forelse (array_reverse($comentarioTarefa['comentarios']) as $comentario)
                        <article wire:key="comentario-modal-{{ $comentario['id'] }}" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="mb-2 flex items-center gap-2">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-700">
                                    {{ strtoupper(substr($comentario['autor'], 0, 1).substr(strstr($comentario['autor'], ' ') ?: '', 1, 1)) }}
                                </span>
                                <div>
                                    <p class="text-sm font-medium text-slate-900">{{ $comentario['autor'] }}</p>
                                    <p class="text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($comentario['criado_em'])->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                            <p class="text-sm whitespace-pre-wrap text-slate-700">{{ $comentario['texto'] }}</p>
                        </article>
                    @empty
                        <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                            <x-icon name="chat-bubble-left-right" class="mb-3 h-8 w-8 text-slate-300" />
                            <p class="text-sm font-medium text-slate-600">Nenhum comentário ainda</p>
                            <p class="mt-1 text-xs text-slate-500">Adicione a primeira observação sobre esta tarefa.</p>
                        </div>
                    @endforelse
                </div>

                <div class="shrink-0 border-t border-slate-100 bg-slate-50 px-5 py-4">
                    <form wire:submit="adicionarComentario" class="space-y-3">
                        <x-textarea
                            wire:model="novoComentario"
                            label="Novo comentário"
                            placeholder="Escreva um comentário..."
                            rows="3"
                        />
                        <div class="flex justify-end gap-3">
                            <x-button flat label="Fechar" wire:click="closeComentarioModal" />
                            <x-button primary type="submit" icon="paper-airplane" label="Enviar" />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
