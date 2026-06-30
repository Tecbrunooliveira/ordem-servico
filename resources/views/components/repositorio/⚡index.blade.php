<?php

use App\Models\RepositorioErro;
use App\Models\Sistema;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

new class extends Component
{
    use WireUiActions;

    public bool $showForm = false;

    public bool $showVisualizar = false;

    public ?int $editingId = null;

    /** @var array<int, array<string, mixed>> */
    public array $registros = [];

    /** @var array<int, array{id: int, nome: string}> */
    public array $sistemas = [];

    /** @var array<string, mixed>|null */
    public ?array $visualizarRegistro = null;

    public string $titulo = '';

    public ?int $sistema_id = null;

    public string $descricao_erro = '';

    public string $solucao = '';

    public string $busca = '';

    public string $filtroSistema = '';

    protected function rules(): array
    {
        $sistemaIds = collect($this->sistemas)->pluck('id')->implode(',');

        return [
            'titulo' => ['required', 'string', 'max:255'],
            'sistema_id' => ['required', 'integer', 'in:'.$sistemaIds],
            'descricao_erro' => ['required', 'string', 'max:50000'],
            'solucao' => ['nullable', 'string', 'max:50000'],
        ];
    }

    public function mount(): void
    {
        $this->carregarSistemas();
        $this->carregarRegistros();
    }

    private function carregarSistemas(): void
    {
        try {
            $this->sistemas = Sistema::query()
                ->orderBy('nome')
                ->get(['id', 'nome', 'ativo'])
                ->map(fn (Sistema $sistema) => [
                    'id' => $sistema->id,
                    'nome' => $sistema->nome,
                    'ativo' => (bool) $sistema->ativo,
                ])
                ->all();
        } catch (\Throwable $exception) {
            report($exception);
            $this->sistemas = [];
        }
    }

    private function carregarRegistros(): void
    {
        try {
            $this->registros = RepositorioErro::query()
                ->with(['sistema:id,nome', 'usuario:id,nome'])
                ->orderByDesc('atualizado_em')
                ->orderByDesc('id')
                ->get()
                ->map(fn (RepositorioErro $registro) => $this->registroParaArray($registro))
                ->all();
        } catch (\Throwable $exception) {
            report($exception);
            $this->registros = [];
            $this->notification()->warning(
                'Banco de dados',
                'Execute as migrations no servidor para habilitar o repositório de erros.',
            );
        }
    }

    /** @return array<string, mixed> */
    private function registroParaArray(RepositorioErro $registro): array
    {
        return [
            'id' => $registro->id,
            'titulo' => $registro->titulo,
            'sistema_id' => $registro->sistema_id,
            'sistema_nome' => $registro->sistema?->nome ?? '—',
            'descricao_erro' => $registro->descricao_erro ?? '',
            'solucao' => $registro->solucao ?? '',
            'autor' => $registro->usuario?->nome ?? '—',
            'criado_em' => $registro->criado_em?->toDateTimeString(),
            'atualizado_em' => $registro->atualizado_em?->toDateTimeString(),
        ];
    }

    /** @return array<string, mixed> */
    private function findRegistro(int $id): array
    {
        foreach ($this->registros as $registro) {
            if ($registro['id'] === $id) {
                return $registro;
            }
        }

        throw new \RuntimeException('Registro não encontrado.');
    }

    public function resumoHtml(?string $html, int $limit = 120): string
    {
        $texto = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', $html ?? '')));
        $texto = preg_replace('/\s+/', ' ', $texto) ?? '';

        if ($texto === '') {
            return '—';
        }

        return mb_strlen($texto) > $limit ? mb_substr($texto, 0, $limit).'...' : $texto;
    }

    /** @return array<int, array{id: int, nome: string}> */
    private function sistemasFormulario(): array
    {
        return collect($this->sistemas)
            ->filter(fn (array $sistema): bool => $sistema['ativo'] || $sistema['id'] === $this->sistema_id)
            ->map(fn (array $sistema) => ['id' => $sistema['id'], 'nome' => $sistema['nome']])
            ->values()
            ->all();
    }

    public function create(): void
    {
        if ($this->sistemasFormulario() === []) {
            $this->notification()->warning(
                'Cadastre um sistema',
                'Antes de registrar erros, cadastre ao menos um tipo de sistema em Cadastros → Sistemas.',
            );

            return;
        }

        $this->closeVisualizar();
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $registro = $this->findRegistro($id);

        $this->closeVisualizar();
        $this->editingId = $registro['id'];
        $this->titulo = $registro['titulo'];
        $this->sistema_id = $registro['sistema_id'];
        $this->descricao_erro = $registro['descricao_erro'];
        $this->solucao = $registro['solucao'];
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        if (trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', $data['descricao_erro']))) === '') {
            $this->addError('descricao_erro', 'Informe a descrição do erro.');

            return;
        }

        $payload = [
            'titulo' => $data['titulo'],
            'sistema_id' => $data['sistema_id'],
            'descricao_erro' => $data['descricao_erro'],
            'solucao' => $data['solucao'] ?: null,
        ];

        if ($this->editingId) {
            RepositorioErro::query()->whereKey($this->editingId)->update($payload);
            $this->notification()->success('Registro atualizado', 'O erro foi salvo no repositório.');
        } else {
            RepositorioErro::query()->create(array_merge($payload, [
                'usuario_id' => auth()->id(),
            ]));
            $this->notification()->success('Registro cadastrado', 'O erro foi adicionado ao repositório.');
        }

        $this->carregarRegistros();
        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(int $id): void
    {
        RepositorioErro::query()->whereKey($id)->delete();
        $this->carregarRegistros();

        if ($this->visualizarRegistro && $this->visualizarRegistro['id'] === $id) {
            $this->closeVisualizar();
        }

        $this->notification()->success('Registro removido', 'O erro foi excluído do repositório.');
    }

    public function visualizar(int $id): void
    {
        $this->showForm = false;
        $this->visualizarRegistro = $this->findRegistro($id);
        $this->showVisualizar = true;
    }

    public function closeVisualizar(): void
    {
        $this->showVisualizar = false;
        $this->visualizarRegistro = null;
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'titulo', 'sistema_id', 'descricao_erro', 'solucao']);
        $this->resetValidation();
    }

    /** @return array<int, array<string, mixed>> */
    private function registrosFiltrados(): array
    {
        $busca = mb_strtolower(trim($this->busca));

        return collect($this->registros)
            ->filter(function (array $registro) use ($busca): bool {
                if ($this->filtroSistema !== '' && (string) $registro['sistema_id'] !== $this->filtroSistema) {
                    return false;
                }

                if ($busca === '') {
                    return true;
                }

                return str_contains(mb_strtolower($registro['titulo']), $busca)
                    || str_contains(mb_strtolower($registro['sistema_nome']), $busca)
                    || str_contains(mb_strtolower($this->resumoHtml($registro['descricao_erro'], 500)), $busca)
                    || str_contains(mb_strtolower($this->resumoHtml($registro['solucao'], 500)), $busca);
            })
            ->values()
            ->all();
    }

    public function with(): array
    {
        return [
            'registrosLista' => $this->registrosFiltrados(),
            'totalRegistros' => count($this->registros),
            'sistemasFormulario' => $this->sistemasFormulario(),
            'uploadMidiaUrl' => route('repositorio.midia'),
        ];
    }
};
?>

<div>
    @if ($showForm)
        <div class="mx-auto max-w-4xl">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-slate-900">
                    {{ $editingId ? 'Editar registro de erro' : 'Novo registro de erro' }}
                </h2>
                <p class="text-sm text-slate-600">Documente o erro e a solução para consulta da equipe.</p>
            </div>

            @if (empty($sistemasFormulario))
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-center">
                    <p class="font-medium text-amber-800">Nenhum sistema cadastrado</p>
                    <p class="mt-1 text-sm text-amber-700">Cadastre um tipo de sistema antes de registrar erros.</p>
                    <div class="mt-4">
                        <x-button primary label="Ir para Sistemas" href="{{ route('sistemas.index') }}" />
                    </div>
                </div>
            @else
                <form wire:submit="save" class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-input wire:model="titulo" label="Título" placeholder="Ex: Falha ao emitir NF-e no módulo fiscal" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-native-select wire:model="sistema_id" label="Sistema">
                                <option value="">Selecione o sistema</option>
                                @foreach ($sistemasFormulario as $sistema)
                                    <option value="{{ $sistema['id'] }}">{{ $sistema['nome'] }}</option>
                                @endforeach
                            </x-native-select>
                        </div>
                    </div>

                    <x-repositorio.rich-content-editor
                        wire-model="descricao_erro"
                        label="Descrição do erro"
                        placeholder="Descreva o erro, passos para reproduzir, mensagens exibidas..."
                        editor-key="repositorio-descricao-{{ $editingId ?? 'novo' }}"
                        :upload-url="$uploadMidiaUrl"
                        min-height="min-h-[10rem]"
                    />

                    <x-repositorio.rich-content-editor
                        wire-model="solucao"
                        label="Solução"
                        placeholder="Descreva como o erro foi resolvido, scripts, configurações..."
                        editor-key="repositorio-solucao-{{ $editingId ?? 'novo' }}"
                        :upload-url="$uploadMidiaUrl"
                        min-height="min-h-[10rem]"
                    />

                    <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <x-button flat label="Cancelar" wire:click="cancel" />
                        <x-button primary type="submit" label="{{ $editingId ? 'Salvar alterações' : 'Cadastrar registro' }}" />
                    </div>
                </form>
            @endif
        </div>
    @else
        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 lg:max-w-2xl">
                <x-input
                    wire:model.live.debounce.300ms="busca"
                    icon="magnifying-glass"
                    label="Buscar"
                    placeholder="Título, sistema ou conteúdo"
                />
                <x-native-select wire:model.live="filtroSistema" label="Sistema">
                    <option value="">Todos</option>
                    @foreach ($sistemas as $sistema)
                        <option value="{{ $sistema['id'] }}">{{ $sistema['nome'] }}</option>
                    @endforeach
                </x-native-select>
            </div>
            <x-button primary icon="plus" label="Novo Registro" wire:click="create" class="!w-auto shrink-0" />
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-5 py-3">Título</th>
                            <th class="px-5 py-3">Sistema</th>
                            <th class="px-5 py-3">Resumo do erro</th>
                            <th class="px-5 py-3">Atualizado</th>
                            <th class="px-5 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($registrosLista as $registro)
                            <tr wire:key="repositorio-{{ $registro['id'] }}" class="hover:bg-slate-50">
                                <td class="px-5 py-4">
                                    <p class="font-medium text-slate-900">{{ $registro['titulo'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $registro['autor'] }}</p>
                                </td>
                                <td class="px-5 py-4 text-slate-700">{{ $registro['sistema_nome'] }}</td>
                                <td class="max-w-xs truncate px-5 py-4 text-slate-600">
                                    {{ $this->resumoHtml($registro['descricao_erro']) }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                    {{ $registro['atualizado_em'] ? \Illuminate\Support\Carbon::parse($registro['atualizado_em'])->format('d/m/Y H:i') : '—' }}
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" wire:click="visualizar({{ $registro['id'] }})" title="Visualizar" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 ring-1 ring-slate-200 hover:bg-slate-100 hover:text-brand-600">
                                            <x-icon name="eye" class="h-4 w-4" />
                                        </button>
                                        <x-mini-button icon="pencil" wire:click="edit({{ $registro['id'] }})" />
                                        <x-mini-button
                                            icon="trash"
                                            negative
                                            wire:click="delete({{ $registro['id'] }})"
                                            wire:confirm="Deseja excluir este registro do repositório?"
                                        />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-slate-600">
                                    @if ($busca || $filtroSistema !== '')
                                        Nenhum registro encontrado com os filtros aplicados.
                                    @else
                                        Nenhum erro documentado ainda. Clique em "Novo Registro" para começar.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t border-slate-100 bg-slate-50">
                        <tr>
                            <td colspan="5" class="px-5 py-3 text-sm text-slate-600">
                                {{ count($registrosLista) }} de {{ $totalRegistros }} {{ $totalRegistros === 1 ? 'registro' : 'registros' }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

    @if ($showVisualizar && $visualizarRegistro)
        @php $registroView = $visualizarRegistro; @endphp
        <div
            wire:key="visualizar-repositorio-{{ $registroView['id'] }}"
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
                class="absolute inset-y-0 right-0 flex h-full w-full flex-col border-l border-slate-200 bg-white shadow-2xl lg:w-[calc(100%-16rem)]"
            >
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-slate-900">{{ $registroView['titulo'] }}</h2>
                        <p class="mt-0.5 text-sm text-slate-600">
                            {{ $registroView['sistema_nome'] }}
                            · Atualizado em {{ $registroView['atualizado_em'] ? \Illuminate\Support\Carbon::parse($registroView['atualizado_em'])->format('d/m/Y H:i') : '—' }}
                        </p>
                    </div>
                    <button type="button" wire:click="closeVisualizar" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                        <x-icon name="x-mark" class="h-4 w-4" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-6">
                    <div class="mx-auto max-w-4xl space-y-6">
                        <section>
                            <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">Descrição do erro</h3>
                            <div class="repositorio-rich-content rich-text-editor__content min-h-[4rem] rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">
                                @if ($registroView['descricao_erro'])
                                    {!! $registroView['descricao_erro'] !!}
                                @else
                                    —
                                @endif
                            </div>
                        </section>

                        <section>
                            <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">Solução</h3>
                            <div class="repositorio-rich-content rich-text-editor__content min-h-[4rem] rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">
                                @if ($registroView['solucao'])
                                    {!! $registroView['solucao'] !!}
                                @else
                                    <span class="text-slate-400">Nenhuma solução registrada.</span>
                                @endif
                            </div>
                        </section>

                        <p class="text-xs text-slate-500">Registrado por {{ $registroView['autor'] }}</p>
                    </div>
                </div>

                <div class="flex shrink-0 justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                    <x-button flat label="Fechar" wire:click="closeVisualizar" />
                    <x-button primary icon="pencil" label="Editar" wire:click="edit({{ $registroView['id'] }})" />
                </div>
            </div>
        </div>
    @endif
</div>
