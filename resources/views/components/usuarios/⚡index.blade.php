<?php

use App\Enums\UsuarioTipo;
use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Validation\Rule;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

new class extends Component
{
    use WireUiActions;

    public bool $showForm = false;

    public bool $showAssociarModal = false;

    public ?int $editingId = null;

    public ?int $associarUsuarioId = null;

    public string $associarUsuarioNome = '';

    /** @var array<int, array<string, mixed>> */
    public array $usuarios = [];

    /** @var array<int, array{id: int, nome: string, documento: string}> */
    public array $clientesDisponiveis = [];

    public string $nome = '';

    public string $email = '';

    public string $senha = '';

    public string $tipo = 'tecnico';

    public string $telefone = '';

    public string $busca = '';

    public string $filtroTipo = '';

    public string $buscaClienteAssociar = '';

    /** @var array<int, int|string> */
    public array $clientesIdsAssociar = [];

    protected function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('usuarios', 'email')->ignore($this->editingId),
            ],
            'senha' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:6', 'max:255'],
            'tipo' => ['required', 'in:'.implode(',', array_column(UsuarioTipo::cases(), 'value'))],
            'telefone' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function mount(): void
    {
        $this->carregarClientesDisponiveis();
        $this->carregarUsuarios();
    }

    private function carregarClientesDisponiveis(): void
    {
        $this->clientesDisponiveis = Cliente::query()
            ->orderBy('nome')
            ->get(['id', 'nome', 'documento'])
            ->map(fn (Cliente $cliente) => [
                'id' => $cliente->id,
                'nome' => $cliente->nome,
                'documento' => $cliente->documento ?? '',
            ])
            ->all();
    }

    private function carregarUsuarios(): void
    {
        $this->usuarios = Usuario::query()
            ->with('clientes:id')
            ->orderBy('nome')
            ->get()
            ->map(fn (Usuario $usuario) => [
                'id' => $usuario->id,
                'nome' => $usuario->nome,
                'email' => $usuario->email,
                'tipo' => $usuario->tipo->value,
                'telefone' => $usuario->telefone ?? '',
                'clientes_ids' => $usuario->clientes->pluck('id')->all(),
            ])
            ->all();
    }

    /** @return array<string, mixed> */
    private function findUsuario(int $id): array
    {
        foreach ($this->usuarios as $usuario) {
            if ($usuario['id'] === $id) {
                return $usuario;
            }
        }

        throw new \RuntimeException('Usuário não encontrado.');
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $usuario = $this->findUsuario($id);

        $this->editingId = $usuario['id'];
        $this->nome = $usuario['nome'];
        $this->email = $usuario['email'];
        $this->senha = '';
        $this->tipo = $usuario['tipo'];
        $this->telefone = $usuario['telefone'];
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            $usuario = Usuario::query()->findOrFail($this->editingId);
            $usuario->fill([
                'nome' => $data['nome'],
                'email' => $data['email'],
                'tipo' => $data['tipo'],
                'telefone' => $data['telefone'],
            ]);

            if (! empty($data['senha'])) {
                $usuario->senha = $data['senha'];
            }

            $usuario->save();

            if ($data['tipo'] !== UsuarioTipo::Cliente->value) {
                $usuario->clientes()->sync([]);
            }

            $this->notification()->success('Usuário atualizado', 'As alterações foram salvas.');
        } else {
            $usuario = Usuario::query()->create([
                'nome' => $data['nome'],
                'email' => $data['email'],
                'senha' => $data['senha'],
                'tipo' => $data['tipo'],
                'telefone' => $data['telefone'],
            ]);

            $this->notification()->success('Usuário cadastrado', 'O acesso foi registrado com sucesso.');
        }

        $this->carregarUsuarios();
        $this->resetForm();
        $this->showForm = false;
    }

    public function openAssociarModal(int $id): void
    {
        $usuario = $this->findUsuario($id);

        if ($usuario['tipo'] !== UsuarioTipo::Cliente->value) {
            return;
        }

        $this->associarUsuarioId = $id;
        $this->associarUsuarioNome = $usuario['nome'];
        $this->clientesIdsAssociar = $usuario['clientes_ids'];
        $this->buscaClienteAssociar = '';
        $this->showAssociarModal = true;
    }

    public function salvarAssociacaoClientes(): void
    {
        if (! $this->associarUsuarioId) {
            return;
        }

        if (count($this->clientesIdsAssociar) === 0) {
            $this->notification()->send([
                'icon' => 'warning',
                'title' => 'Nenhum cliente selecionado',
                'description' => 'Selecione ao menos um cliente para associar.',
                'timeout' => 3000,
            ]);

            return;
        }

        $index = collect($this->usuarios)->search(fn (array $u) => $u['id'] === $this->associarUsuarioId);

        if ($index === false) {
            return;
        }

        $usuario = Usuario::query()->findOrFail($this->associarUsuarioId);
        $usuario->clientes()->sync(array_values(array_map('intval', $this->clientesIdsAssociar)));

        $this->carregarUsuarios();

        $this->notification()->send([
            'icon' => 'success',
            'title' => 'Clientes associados',
            'timeout' => 3000,
        ]);

        $this->closeAssociarModal();
    }

    public function closeAssociarModal(): void
    {
        $this->showAssociarModal = false;
        $this->associarUsuarioId = null;
        $this->associarUsuarioNome = '';
        $this->clientesIdsAssociar = [];
        $this->buscaClienteAssociar = '';
    }

    public function delete(int $id): void
    {
        if ($id === auth()->id()) {
            $this->notification()->error('Ação não permitida', 'Você não pode excluir o próprio usuário logado.');

            return;
        }

        Usuario::query()->whereKey($id)->delete();
        $this->carregarUsuarios();

        $this->notification()->success('Usuário removido', 'O registro foi excluído.');
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'nome', 'email', 'senha', 'telefone']);
        $this->tipo = UsuarioTipo::Tecnico->value;
        $this->resetValidation();
    }

    public function iniciais(string $nome): string
    {
        $partes = preg_split('/\s+/', trim($nome)) ?: [];

        return strtoupper(substr($partes[0] ?? '', 0, 1).substr($partes[1] ?? '', 0, 1));
    }

    /** @return array<int, array{id: int, nome: string, documento: string}> */
    public function clientesParaAssociar(): array
    {
        $busca = mb_strtolower(trim($this->buscaClienteAssociar));

        return collect($this->clientesDisponiveis)
            ->filter(function (array $cliente) use ($busca): bool {
                if ($busca === '') {
                    return true;
                }

                return str_contains(mb_strtolower($cliente['nome']), $busca)
                    || str_contains(preg_replace('/\D/', '', $cliente['documento']), preg_replace('/\D/', '', $busca));
            })
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    public function nomesClientesVinculados(array $clientesIds): array
    {
        return collect($this->clientesDisponiveis)
            ->filter(fn (array $cliente) => in_array($cliente['id'], $clientesIds, true))
            ->pluck('nome')
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function usuariosFiltrados(): array
    {
        $busca = mb_strtolower(trim($this->busca));

        return collect($this->usuarios)
            ->filter(function (array $usuario) use ($busca): bool {
                if ($this->filtroTipo !== '' && $usuario['tipo'] !== $this->filtroTipo) {
                    return false;
                }

                if ($busca === '') {
                    return true;
                }

                return str_contains(mb_strtolower($usuario['nome']), $busca)
                    || str_contains(mb_strtolower($usuario['email']), $busca)
                    || str_contains($usuario['telefone'], $busca);
            })
            ->values()
            ->all();
    }

    public function limparFiltros(): void
    {
        $this->reset(['busca', 'filtroTipo']);
    }

    public function with(): array
    {
        return [
            'tipos' => UsuarioTipo::options(),
            'usuariosLista' => $this->usuariosFiltrados(),
            'totalUsuarios' => count($this->usuarios),
            'clientesAssociarLista' => $this->clientesParaAssociar(),
        ];
    }
};
?>

<div>
    @if ($showForm)
        <div class="mx-auto max-w-2xl">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-slate-900">
                    {{ $editingId ? 'Editar Usuário' : 'Novo Usuário' }}
                </h2>
                <p class="text-sm text-slate-600">Cadastre quem poderá acessar o sistema e defina o tipo de permissão.</p>
            </div>

            <form wire:submit="save" class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <x-input wire:model="nome" label="Nome" placeholder="Ex: João Silva" />

                <x-input wire:model="email" label="E-mail" type="email" placeholder="usuario@empresa.com.br" />

                <x-input
                    wire:model="senha"
                    label="{{ $editingId ? 'Nova senha' : 'Senha' }}"
                    type="password"
                    placeholder="{{ $editingId ? 'Deixe em branco para manter a atual' : 'Mínimo 6 caracteres' }}"
                />

                <x-phone
                    wire:model="telefone"
                    label="Telefone"
                    :mask="['(##) ####-####', '(##) #####-####']"
                    emit-formatted
                    placeholder="(11) 99999-9999"
                />

                <x-native-select wire:model="tipo" label="Tipo">
                    @foreach ($tipos as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-native-select>

                <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    {{ \App\Enums\UsuarioTipo::from($tipo)->descricaoAcesso() }}
                </div>

                @if ($tipo === 'cliente')
                    <p class="text-xs text-slate-500">
                        Após cadastrar, use o menu <strong>Associar clientes</strong> na listagem para vincular os clientes deste usuário.
                    </p>
                @endif

                <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                    <x-button flat label="Cancelar" wire:click="cancel" />
                    <x-button primary type="submit" label="{{ $editingId ? 'Salvar alterações' : 'Cadastrar usuário' }}" />
                </div>
            </form>
        </div>
    @else
        <div
            class="mb-4 space-y-3"
            x-data="{ filtrosAbertos: false }"
        >
            <div class="page-list-toolbar-row">
                <div class="min-w-0">
                    <x-input
                        wire:model.live.debounce.300ms="busca"
                        icon="magnifying-glass"
                        label="Pesquisar"
                        placeholder="Nome, e-mail ou telefone"
                    />
                </div>

                <button
                    type="button"
                    x-on:click="filtrosAbertos = ! filtrosAbertos"
                    class="inline-flex h-10 shrink-0 items-center gap-2 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    <x-icon name="plus" class="h-4 w-4 transition-transform" x-bind:class="filtrosAbertos && 'rotate-45'" />
                    Filtros
                </button>

                <x-button primary icon="plus" label="Novo Usuário" wire:click="create" class="!w-auto shrink-0 whitespace-nowrap" />
            </div>

            <div
                x-show="filtrosAbertos"
                x-transition
                x-cloak
                class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
            >
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <x-native-select wire:model.live="filtroTipo" label="Tipo">
                        <option value="">Todos</option>
                        @foreach ($tipos as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-native-select>
                </div>

                @if ($busca || $filtroTipo)
                    <div class="mt-4 flex justify-end border-t border-slate-100 pt-4">
                        <x-button flat label="Limpar filtros" wire:click="limparFiltros" />
                    </div>
                @endif
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-5 py-3">Usuário</th>
                            <th class="px-5 py-3">Contato</th>
                            <th class="px-5 py-3">Tipo</th>
                            <th class="px-5 py-3">Clientes</th>
                            <th class="px-5 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($usuariosLista as $usuario)
                            @php
                                $tipoEnum = \App\Enums\UsuarioTipo::from($usuario['tipo']);
                                $clientesVinculados = $this->nomesClientesVinculados($usuario['clientes_ids']);
                            @endphp
                            <tr wire:key="usuario-{{ $usuario['id'] }}" class="hover:bg-slate-50">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">
                                            {{ $this->iniciais($usuario['nome']) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate font-medium text-slate-900">{{ $usuario['nome'] }}</p>
                                            <p class="truncate text-xs text-slate-600">{{ $usuario['email'] }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-700">
                                    {{ $usuario['telefone'] ?: '—' }}
                                </td>
                                <td class="px-5 py-4">
                                    <span @class(['inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold', $tipoEnum->badgeClass()])>
                                        {{ $tipoEnum->label() }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    @if ($tipoEnum === \App\Enums\UsuarioTipo::Cliente)
                                        @if (count($clientesVinculados))
                                            <div class="space-y-1.5">
                                                <p class="text-xs font-medium text-slate-500">{{ count($clientesVinculados) }} {{ count($clientesVinculados) === 1 ? 'cliente' : 'clientes' }}</p>
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach (array_slice($clientesVinculados, 0, 2) as $nomeCliente)
                                                        <span class="inline-flex max-w-[10rem] truncate rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700 ring-1 ring-slate-200">
                                                            {{ $nomeCliente }}
                                                        </span>
                                                    @endforeach
                                                    @if (count($clientesVinculados) > 2)
                                                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600 ring-1 ring-slate-200">
                                                            +{{ count($clientesVinculados) - 2 }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-xs text-amber-700">
                                                <x-icon name="exclamation-circle" class="h-4 w-4" />
                                                Sem associação
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" wire:click="edit({{ $usuario['id'] }})" title="Editar" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 ring-1 ring-slate-200 hover:bg-slate-100 hover:text-brand-600">
                                            <x-icon name="pencil" class="h-4 w-4" />
                                        </button>

                                        <button type="button" wire:click="delete({{ $usuario['id'] }})" wire:confirm="Deseja excluir este usuário?" title="Excluir" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-600 ring-1 ring-red-200 hover:bg-red-50">
                                            <x-icon name="trash" class="h-4 w-4" />
                                        </button>

                                        @if ($tipoEnum === \App\Enums\UsuarioTipo::Cliente)
                                            <x-dropdown
                                                position="bottom-end"
                                                width="sm"
                                                wire:key="usuario-menu-{{ $usuario['id'] }}"
                                            >
                                                <x-slot name="trigger">
                                                    <button
                                                        type="button"
                                                        title="Mais opções"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 ring-1 ring-slate-200 hover:bg-slate-100 hover:text-brand-600"
                                                    >
                                                        <x-icon name="ellipsis-vertical" class="h-4 w-4" />
                                                    </button>
                                                </x-slot>

                                                <button
                                                    type="button"
                                                    wire:click.stop="openAssociarModal({{ $usuario['id'] }})"
                                                    class="flex w-full cursor-pointer items-center rounded-md px-4 py-2 text-sm text-secondary-600 transition-colors duration-150 hover:bg-secondary-100 hover:text-secondary-900"
                                                >
                                                    <x-icon name="link" class="mr-2 h-5 w-5" />
                                                    Associar clientes
                                                </button>
                                            </x-dropdown>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-slate-600">
                                    @if ($busca || $filtroTipo)
                                        Nenhum usuário encontrado com os filtros aplicados.
                                    @else
                                        Nenhum usuário cadastrado. Clique em "Novo Usuário" para começar.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t border-slate-100 bg-slate-50">
                        <tr>
                            <td colspan="5" class="px-5 py-3 text-sm text-slate-600">
                                @if ($busca || $filtroTipo)
                                    Exibindo {{ count($usuariosLista) }} de {{ $totalUsuarios }} {{ $totalUsuarios === 1 ? 'usuário' : 'usuários' }}
                                @else
                                    {{ $totalUsuarios }} {{ $totalUsuarios === 1 ? 'usuário cadastrado' : 'usuários cadastrados' }}
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

    @if ($showAssociarModal)
        <div
            wire:key="associar-clientes-modal"
            class="fixed inset-0 z-[90] flex items-center justify-center p-4 sm:p-6"
            role="dialog"
            aria-modal="true"
            x-data
            x-on:keydown.escape.window="$wire.closeAssociarModal()"
        >
            <button
                type="button"
                class="absolute inset-0 bg-slate-900/45 backdrop-blur-[2px]"
                wire:click="closeAssociarModal"
                aria-label="Fechar"
            ></button>

            <div class="relative flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-slate-900">Associar clientes</h3>
                        <p class="mt-0.5 truncate text-sm text-slate-500">{{ $associarUsuarioNome }}</p>
                    </div>
                    <button
                        type="button"
                        wire:click="closeAssociarModal"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                    >
                        <x-icon name="x-mark" class="h-4 w-4" />
                    </button>
                </div>

                <div class="shrink-0 border-b border-slate-100 px-5 py-4">
                    <x-input
                        wire:model.live.debounce.300ms="buscaClienteAssociar"
                        icon="magnifying-glass"
                        label="Buscar cliente"
                        placeholder="Razão social ou CNPJ"
                    />
                    <p class="mt-2 text-xs text-slate-500">
                        {{ count($clientesIdsAssociar) }} {{ count($clientesIdsAssociar) === 1 ? 'cliente selecionado' : 'clientes selecionados' }}
                    </p>
                </div>

                <div class="min-h-0 flex-1 space-y-2 overflow-y-auto px-5 py-4">
                    @forelse ($clientesAssociarLista as $cliente)
                        <label
                            wire:key="associar-cliente-{{ $cliente['id'] }}"
                            @class([
                                'flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition-colors',
                                'border-brand-300 bg-brand-50/50' => in_array($cliente['id'], $clientesIdsAssociar),
                                'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50' => ! in_array($cliente['id'], $clientesIdsAssociar),
                            ])
                        >
                            <input
                                type="checkbox"
                                wire:model.live="clientesIdsAssociar"
                                value="{{ $cliente['id'] }}"
                                class="mt-1 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                            >
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-900">{{ $cliente['nome'] }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $cliente['documento'] }}</p>
                            </div>
                        </label>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                            <x-icon name="magnifying-glass" class="mx-auto h-8 w-8 text-slate-300" />
                            <p class="mt-2 text-sm text-slate-600">Nenhum cliente encontrado</p>
                            @if ($buscaClienteAssociar)
                                <p class="mt-1 text-xs text-slate-500">Tente buscar por outro nome ou CNPJ.</p>
                            @endif
                        </div>
                    @endforelse
                </div>

                <div class="flex shrink-0 justify-end gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4">
                    <x-button flat label="Cancelar" wire:click="closeAssociarModal" />
                    <x-button primary icon="check" label="Salvar associação" wire:click="salvarAssociacaoClientes" />
                </div>
            </div>
        </div>
    @endif
</div>
