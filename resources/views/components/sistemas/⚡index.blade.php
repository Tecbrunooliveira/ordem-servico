<?php

use App\Models\Sistema;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

new class extends Component
{
    use WireUiActions;

    public bool $showForm = false;

    public ?int $editingId = null;

    /** @var array<int, array<string, mixed>> */
    public array $sistemas = [];

    public string $nome = '';

    public string $descricao = '';

    public bool $ativo = true;

    public string $busca = '';

    public string $filtroAtivo = '';

    protected function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'ativo' => ['boolean'],
        ];
    }

    public function mount(): void
    {
        $this->carregarSistemas();
    }

    private function carregarSistemas(): void
    {
        try {
            $this->sistemas = Sistema::query()
                ->withCount('erros')
                ->orderBy('nome')
                ->get()
                ->map(fn (Sistema $sistema) => $this->sistemaParaArray($sistema))
                ->all();
        } catch (\Throwable $exception) {
            report($exception);
            $this->sistemas = [];
            $this->notification()->warning(
                'Banco de dados',
                'Execute as migrations no servidor para habilitar o cadastro de sistemas.',
            );
        }
    }

    /** @return array<string, mixed> */
    private function sistemaParaArray(Sistema $sistema): array
    {
        return [
            'id' => $sistema->id,
            'nome' => $sistema->nome,
            'descricao' => $sistema->descricao ?? '',
            'ativo' => (bool) $sistema->ativo,
            'erros_count' => (int) ($sistema->erros_count ?? $sistema->erros()->count()),
        ];
    }

    /** @return array<string, mixed> */
    private function findSistema(int $id): array
    {
        foreach ($this->sistemas as $sistema) {
            if ($sistema['id'] === $id) {
                return $sistema;
            }
        }

        throw new \RuntimeException('Sistema não encontrado.');
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $sistema = $this->findSistema($id);

        $this->editingId = $sistema['id'];
        $this->nome = $sistema['nome'];
        $this->descricao = $sistema['descricao'];
        $this->ativo = $sistema['ativo'];
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            Sistema::query()->whereKey($this->editingId)->update($data);
            $this->notification()->success('Sistema atualizado', 'O tipo de sistema foi salvo com sucesso.');
        } else {
            Sistema::query()->create($data);
            $this->notification()->success('Sistema cadastrado', 'O tipo de sistema foi adicionado.');
        }

        $this->carregarSistemas();
        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(int $id): void
    {
        $sistema = Sistema::query()->withCount('erros')->find($id);

        if (! $sistema) {
            return;
        }

        if ($sistema->erros_count > 0) {
            $this->notification()->error(
                'Não é possível excluir',
                'Este sistema possui registros no repositório de erros. Remova ou reassocie os registros antes.',
            );

            return;
        }

        $sistema->delete();
        $this->carregarSistemas();
        $this->notification()->success('Sistema removido', 'O registro foi excluído.');
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'nome', 'descricao']);
        $this->ativo = true;
        $this->resetValidation();
    }

    /** @return array<int, array<string, mixed>> */
    private function sistemasFiltrados(): array
    {
        $busca = mb_strtolower(trim($this->busca));

        return collect($this->sistemas)
            ->filter(function (array $sistema) use ($busca): bool {
                if ($this->filtroAtivo === '1' && ! $sistema['ativo']) {
                    return false;
                }

                if ($this->filtroAtivo === '0' && $sistema['ativo']) {
                    return false;
                }

                if ($busca === '') {
                    return true;
                }

                return str_contains(mb_strtolower($sistema['nome']), $busca)
                    || str_contains(mb_strtolower($sistema['descricao']), $busca);
            })
            ->values()
            ->all();
    }

    public function with(): array
    {
        return [
            'sistemasLista' => $this->sistemasFiltrados(),
            'totalSistemas' => count($this->sistemas),
        ];
    }
};
?>

<div>
    @if ($showForm)
        <div class="mx-auto max-w-xl">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-slate-900">
                    {{ $editingId ? 'Editar Sistema' : 'Novo Sistema' }}
                </h2>
                <p class="text-sm text-slate-600">Cadastre os tipos de sistemas usados no repositório de erros.</p>
            </div>

            <form wire:submit="save" class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <x-input wire:model="nome" label="Nome do sistema" placeholder="Ex: ERP, PDV, Portal Web" />
                <x-textarea wire:model="descricao" label="Descrição (opcional)" placeholder="Breve descrição do sistema" rows="3" />
                <x-toggle wire:model="ativo" label="Ativo" />

                <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                    <x-button flat label="Cancelar" wire:click="cancel" />
                    <x-button primary type="submit" label="{{ $editingId ? 'Salvar alterações' : 'Cadastrar sistema' }}" />
                </div>
            </form>
        </div>
    @else
        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 lg:max-w-2xl">
                <x-input
                    wire:model.live.debounce.300ms="busca"
                    icon="magnifying-glass"
                    label="Buscar"
                    placeholder="Nome ou descrição"
                />
                <x-native-select wire:model.live="filtroAtivo" label="Status">
                    <option value="">Todos</option>
                    <option value="1">Ativos</option>
                    <option value="0">Inativos</option>
                </x-native-select>
            </div>
            <x-button primary icon="plus" label="Novo Sistema" wire:click="create" class="!w-auto shrink-0" />
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-5 py-3">Sistema</th>
                            <th class="px-5 py-3">Descrição</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-center">Erros</th>
                            <th class="px-5 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($sistemasLista as $sistema)
                            <tr wire:key="sistema-{{ $sistema['id'] }}" class="hover:bg-slate-50">
                                <td class="px-5 py-4 font-medium text-slate-900">{{ $sistema['nome'] }}</td>
                                <td class="max-w-xs truncate px-5 py-4 text-slate-600">{{ $sistema['descricao'] ?: '—' }}</td>
                                <td class="px-5 py-4">
                                    @if ($sistema['ativo'])
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">Ativo</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">Inativo</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-center text-slate-600">{{ $sistema['erros_count'] }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <x-mini-button icon="pencil" wire:click="edit({{ $sistema['id'] }})" />
                                        <x-mini-button
                                            icon="trash"
                                            negative
                                            wire:click="delete({{ $sistema['id'] }})"
                                            wire:confirm="Deseja excluir este sistema?"
                                        />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-slate-600">
                                    @if ($busca || $filtroAtivo !== '')
                                        Nenhum sistema encontrado com os filtros aplicados.
                                    @else
                                        Nenhum sistema cadastrado. Clique em "Novo Sistema" para começar.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t border-slate-100 bg-slate-50">
                        <tr>
                            <td colspan="5" class="px-5 py-3 text-sm text-slate-600">
                                {{ count($sistemasLista) }} de {{ $totalSistemas }} {{ $totalSistemas === 1 ? 'sistema cadastrado' : 'sistemas cadastrados' }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
</div>
