<?php

use App\Models\Cliente;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

new class extends Component
{
    use WireUiActions;

    public bool $showForm = false;

    public ?int $editingId = null;

    /** @var array<int, array<string, mixed>> */
    public array $clientes = [];

    public string $nome = '';

    public string $documento = '';

    public string $email = '';

    public string $telefone = '';

    public string $cidade = '';

    public string $estado = '';

    public string $rua = '';

    public string $numero = '';

    public string $bairro = '';

    public bool $ativo = true;

    public string $busca = '';

    public string $filtroAtivo = '';

    public ?string $alerta = null;

    public string $alertaTipo = '';

    protected function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'documento' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'cidade' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'string', 'max:2'],
            'rua' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'bairro' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function mount(): void
    {
        $this->carregarClientes();
    }

    private function carregarClientes(): void
    {
        try {
            $this->clientes = Cliente::query()
                ->orderBy('nome')
                ->get()
                ->map(fn (Cliente $cliente) => $this->clienteParaArray($cliente))
                ->all();
        } catch (\Throwable $exception) {
            report($exception);
            $this->clientes = [];
            $this->alerta('erro', 'Não foi possível carregar os clientes. Execute as migrations no servidor.');
        }
    }

    /** @return array<string, mixed> */
    private function clienteParaArray(Cliente $cliente): array
    {
        return [
            'id' => $cliente->id,
            'nome' => $cliente->nome,
            'documento' => $cliente->documento ?? '',
            'email' => $cliente->email ?? '',
            'telefone' => $cliente->telefone ?? '',
            'cidade' => $cliente->cidade ?? '',
            'estado' => $cliente->estado ?? '',
            'rua' => $cliente->rua ?? '',
            'numero' => $cliente->numero ?? '',
            'bairro' => $cliente->bairro ?? '',
            'ativo' => (bool) $cliente->ativo,
        ];
    }

    /** @return array<string, mixed> */
    private function atributosDoFormulario(array $data): array
    {
        $nullable = static fn (mixed $value): ?string => is_string($value) && trim($value) === '' ? null : (is_string($value) ? $value : null);

        return [
            'nome' => $data['nome'],
            'documento' => $nullable($data['documento'] ?? null),
            'email' => $nullable($data['email'] ?? null),
            'telefone' => $nullable($data['telefone'] ?? null),
            'cidade' => $nullable($data['cidade'] ?? null),
            'estado' => $nullable($data['estado'] ?? null),
            'rua' => $nullable($data['rua'] ?? null),
            'numero' => $nullable($data['numero'] ?? null),
            'bairro' => $nullable($data['bairro'] ?? null),
            'ativo' => $this->ativo,
        ];
    }

    private function alerta(string $tipo, string $mensagem): void
    {
        $this->alertaTipo = $tipo;
        $this->alerta = $mensagem;

        $this->notification()->send([
            'title' => $tipo === 'erro' ? 'Erro' : 'Sucesso',
            'description' => $mensagem,
            'timeout' => 5000,
        ]);
    }

    private function limparAlerta(): void
    {
        $this->alerta = null;
        $this->alertaTipo = '';
    }

    /** @return array<string, mixed> */
    private function findCliente(int $id): array
    {
        foreach ($this->clientes as $cliente) {
            if ($cliente['id'] === $id) {
                return $cliente;
            }
        }

        abort(404);
    }

    /** @return array<int, array<string, mixed>> */
    private function clientesOrdenados(): array
    {
        $clientes = $this->clientes;
        usort($clientes, fn (array $a, array $b) => strcasecmp($a['nome'], $b['nome']));

        return $clientes;
    }

    /** @return array<int, array<string, mixed>> */
    private function clientesFiltrados(): array
    {
        $clientes = $this->clientesOrdenados();
        $termo = trim($this->busca);

        if ($termo !== '') {
            $termoNormalizado = mb_strtolower($termo);
            $termoDocumento = preg_replace('/\D/', '', $termo);

            $clientes = array_values(array_filter(
                $clientes,
                function (array $cliente) use ($termoNormalizado, $termoDocumento): bool {
                    if (str_contains(mb_strtolower($cliente['nome']), $termoNormalizado)) {
                        return true;
                    }

                    if ($termoDocumento !== '' && str_contains(preg_replace('/\D/', '', $cliente['documento'] ?? ''), $termoDocumento)) {
                        return true;
                    }

                    if (str_contains(mb_strtolower($cliente['email'] ?? ''), $termoNormalizado)) {
                        return true;
                    }

                    return str_contains(mb_strtolower($cliente['cidade'] ?? ''), $termoNormalizado);
                },
            ));
        }

        return $this->aplicarFiltroAtivo($clientes);
    }

    /** @param  array<int, array<string, mixed>>  $clientes */
    private function aplicarFiltroAtivo(array $clientes): array
    {
        if ($this->filtroAtivo === '') {
            return $clientes;
        }

        $ativo = $this->filtroAtivo === '1';

        return array_values(array_filter(
            $clientes,
            fn (array $cliente): bool => (bool) ($cliente['ativo'] ?? true) === $ativo,
        ));
    }

    public function limparFiltros(): void
    {
        $this->busca = '';
        $this->filtroAtivo = '';
    }

    public function iniciaisCliente(string $nome): string
    {
        $partes = array_values(array_filter(preg_split('/\s+/', trim($nome)) ?: []));

        if (count($partes) >= 2) {
            return mb_strtoupper(mb_substr($partes[0], 0, 1).mb_substr($partes[1], 0, 1));
        }

        return mb_strtoupper(mb_substr($nome, 0, 2));
    }

    /** @return array{total: int, ativos: int, inativos: int} */
    public function contagemClientes(): array
    {
        $ativos = 0;
        $inativos = 0;

        foreach ($this->clientes as $cliente) {
            if ($cliente['ativo'] ?? true) {
                $ativos++;
            } else {
                $inativos++;
            }
        }

        return [
            'total' => count($this->clientes),
            'ativos' => $ativos,
            'inativos' => $inativos,
        ];
    }

    public function create(): void
    {
        $this->limparAlerta();
        $this->resetForm();
        $this->showForm = true;
    }

    /** @param  array<string, string>  $dados */
    public function aplicarDadosCnpjMapeados(array $dados): void
    {
        $dados = validator($dados, [
            'nome' => ['nullable', 'string', 'max:255'],
            'documento' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'max:2'],
            'rua' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'bairro' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $this->nome = $dados['nome'] ?? $this->nome;
        $this->documento = $dados['documento'] ?? $this->documento;
        $this->email = $dados['email'] ?? '';
        $this->telefone = $dados['telefone'] ?? '';
        $this->cidade = $dados['cidade'] ?? '';
        $this->estado = $dados['estado'] ?? '';
        $this->rua = $dados['rua'] ?? '';
        $this->numero = $dados['numero'] ?? '';
        $this->bairro = $dados['bairro'] ?? '';
    }

    public function edit(int $id): void
    {
        $cliente = $this->findCliente($id);

        $this->editingId = $cliente['id'];
        $this->nome = $cliente['nome'];
        $this->documento = $cliente['documento'];
        $this->email = $cliente['email'];
        $this->telefone = $cliente['telefone'];
        $this->cidade = $cliente['cidade'];
        $this->estado = $cliente['estado'];
        $this->rua = $cliente['rua'] ?? '';
        $this->numero = $cliente['numero'] ?? '';
        $this->bairro = $cliente['bairro'] ?? '';
        $this->ativo = $cliente['ativo'];
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->limparAlerta();
        $this->estado = strtoupper(substr(trim($this->estado), 0, 2));

        $data = $this->validate();
        $atributos = $this->atributosDoFormulario($data);
        $wasEditing = (bool) $this->editingId;

        try {
            if ($wasEditing) {
                $cliente = Cliente::query()->findOrFail($this->editingId);
                $cliente->update($atributos);
            } else {
                Cliente::query()->create($atributos);
            }

            $this->carregarClientes();
        } catch (\Throwable $exception) {
            report($exception);

            $mensagem = config('app.debug')
                ? $exception->getMessage()
                : 'Não foi possível gravar no banco. Execute: php83 artisan migrate --force';

            $this->alerta('erro', $mensagem);

            return;
        }

        $this->alerta(
            'sucesso',
            $wasEditing ? 'Cliente atualizado com sucesso.' : 'Cliente cadastrado com sucesso.',
        );

        $this->resetForm();
        $this->showForm = false;
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(int $id): void
    {
        $this->limparAlerta();

        try {
            Cliente::query()->whereKey($id)->delete();
            $this->carregarClientes();
            $this->alerta('sucesso', 'Cliente removido com sucesso.');
        } catch (\Throwable $exception) {
            report($exception);
            $this->alerta('erro', 'Não foi possível excluir o cliente.');
        }
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId',
            'nome',
            'documento',
            'email',
            'telefone',
            'cidade',
            'estado',
            'rua',
            'numero',
            'bairro',
        ]);
        $this->ativo = true;
        $this->resetValidation();
    }

    public function with(): array
    {
        $contagem = $this->contagemClientes();

        return [
            'clientesLista' => $this->clientesFiltrados(),
            'totalClientes' => $contagem['total'],
            'totalAtivos' => $contagem['ativos'],
            'totalInativos' => $contagem['inativos'],
        ];
    }
};
?>

<div>
    @if ($alerta)
        <div
            wire:key="cliente-alerta"
            @class([
                'mb-4 rounded-lg border px-4 py-3 text-sm font-medium',
                'border-emerald-200 bg-emerald-50 text-emerald-800' => $alertaTipo === 'sucesso',
                'border-red-200 bg-red-50 text-red-800' => $alertaTipo === 'erro',
            ])
        >
            {{ $alerta }}
        </div>
    @endif

    @if ($showForm)
        <div class="mx-auto max-w-3xl">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-slate-900">
                    {{ $editingId ? 'Editar Cliente' : 'Novo Cliente' }}
                </h2>
                <p class="text-sm text-slate-600">Preencha os dados do cliente para vincular às ordens de serviço.</p>
            </div>

            <form wire:submit="save" class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-cnpj-lookup-field
                        wire-model="documento"
                        apply-method="aplicarDadosCnpjMapeados"
                        variant="cliente"
                        :auto-fetch="! $editingId"
                        class="sm:col-span-2"
                    />
                    <div class="sm:col-span-2">
                        <x-input wire:model="nome" label="Nome / Razão Social" placeholder="Ex: Cliente ABC Ltda" />
                    </div>
                    <x-phone
                        wire:model="telefone"
                        label="Telefone"
                        :mask="['(##) ####-####', '(##) #####-####']"
                        emit-formatted
                        placeholder="(11) 99999-9999"
                    />
                    <x-input wire:model="email" label="E-mail" type="email" placeholder="contato@empresa.com" />
                    <div class="sm:col-span-2">
                        <x-input wire:model="rua" label="Rua" placeholder="Ex: Av. Paulista" />
                    </div>
                    <x-input wire:model="numero" label="Número" placeholder="Ex: 1000" />
                    <x-input wire:model="bairro" label="Bairro" placeholder="Ex: Bela Vista" />
                    <x-input wire:model="cidade" label="Cidade" placeholder="São Paulo" />
                    <x-input wire:model="estado" label="UF" placeholder="SP" maxlength="2" />
                    <div>
                        <x-toggle wire:model="ativo" label="Cliente ativo" />
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                    <x-button flat label="Cancelar" wire:click="cancel" />
                    <x-button primary type="submit" label="{{ $editingId ? 'Salvar alterações' : 'Cadastrar cliente' }}" />
                </div>
            </form>
        </div>
    @else
        @php $temFiltros = $busca !== '' || $filtroAtivo !== ''; @endphp

        <div class="mb-4 space-y-4">
            <div class="page-list-stats">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $totalClientes }}</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-emerald-700">Ativos</p>
                    <p class="mt-1 text-2xl font-semibold text-emerald-800">{{ $totalAtivos }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Inativos</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-700">{{ $totalInativos }}</p>
                </div>
            </div>

            <div class="page-list-toolbar">
                <div class="page-list-toolbar-filters">
                    <div class="min-w-0 flex-1">
                        <x-input
                            wire:model.live.debounce.300ms="busca"
                            icon="magnifying-glass"
                            label="Pesquisar"
                            placeholder="Nome, CNPJ, e-mail ou cidade"
                        />
                    </div>

                    <div class="filter-status">
                        <x-native-select wire:model.live="filtroAtivo" label="Status">
                            <option value="">Todos</option>
                            <option value="1">Ativos</option>
                            <option value="0">Inativos</option>
                        </x-native-select>
                    </div>
                </div>

                <button type="button" wire:click="create" class="btn-primary shrink-0">
                    <x-icon name="plus" class="h-4 w-4" />
                    Novo Cliente
                </button>
            </div>

            @if ($temFiltros)
                <div class="flex items-center justify-between rounded-lg border border-brand-100 bg-brand-50/50 px-4 py-2.5 text-sm text-slate-700">
                    <span>
                        Exibindo <strong>{{ count($clientesLista) }}</strong> de <strong>{{ $totalClientes }}</strong> clientes
                    </span>
                    <button
                        type="button"
                        wire:click="limparFiltros"
                        class="text-sm font-medium text-brand-600 hover:text-brand-700"
                    >
                        Limpar filtros
                    </button>
                </div>
            @endif
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-4 py-2.5">Cliente</th>
                            <th class="px-4 py-2.5">Contato</th>
                            <th class="px-4 py-2.5">Endereço</th>
                            <th class="whitespace-nowrap px-4 py-2.5">Status</th>
                            <th class="w-24 whitespace-nowrap px-4 py-2.5 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($clientesLista as $cliente)
                            <tr
                                wire:key="cliente-{{ $cliente['id'] }}"
                                @class([
                                    'hover:bg-slate-50/80',
                                    'opacity-60' => ! ($cliente['ativo'] ?? true),
                                ])
                            >
                                <td class="px-4 py-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700 ring-2 ring-white">
                                            {{ $this->iniciaisCliente($cliente['nome']) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate font-medium text-slate-900">{{ $cliente['nome'] }}</p>
                                            <p class="truncate font-mono text-xs text-slate-500">
                                                {{ $cliente['documento'] ?: 'Sem documento' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="space-y-1">
                                        @if ($cliente['telefone'])
                                            <p class="flex items-center gap-1.5 text-slate-800">
                                                <x-icon name="phone" class="h-3.5 w-3.5 shrink-0 text-slate-400" />
                                                <span>{{ $cliente['telefone'] }}</span>
                                            </p>
                                        @endif
                                        @if ($cliente['email'])
                                            <p class="flex items-center gap-1.5 text-xs text-slate-600">
                                                <x-icon name="envelope" class="h-3.5 w-3.5 shrink-0 text-slate-400" />
                                                <span class="truncate">{{ $cliente['email'] }}</span>
                                            </p>
                                        @endif
                                        @if (! $cliente['telefone'] && ! $cliente['email'])
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @php $endereco = \App\Support\ClienteStore::enderecoCompleto($cliente); @endphp
                                    <p class="max-w-xs truncate text-slate-800" title="{{ $endereco }}">
                                        {{ $endereco }}
                                    </p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset',
                                        'bg-emerald-50 text-emerald-700 ring-emerald-200' => $cliente['ativo'],
                                        'bg-slate-100 text-slate-600 ring-slate-200' => ! $cliente['ativo'],
                                    ])>
                                        {{ $cliente['ativo'] ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-1.5">
                                        <button
                                            type="button"
                                            wire:click="edit({{ $cliente['id'] }})"
                                            title="Editar"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-600 ring-1 ring-slate-200 hover:bg-slate-100 hover:text-brand-600"
                                        >
                                            <x-icon name="pencil" class="h-4 w-4" />
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="delete({{ $cliente['id'] }})"
                                            wire:confirm="Deseja excluir este cliente?"
                                            title="Excluir"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-md text-red-600 ring-1 ring-red-200 hover:bg-red-50"
                                        >
                                            <x-icon name="trash" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-14">
                                    <div class="flex flex-col items-center justify-center text-center">
                                        <span class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                            <x-icon name="users" class="h-6 w-6" />
                                        </span>
                                        @if ($temFiltros)
                                            <p class="font-medium text-slate-900">Nenhum cliente encontrado</p>
                                            <p class="mt-1 max-w-sm text-sm text-slate-600">
                                                Ajuste a pesquisa ou os filtros para localizar o cadastro desejado.
                                            </p>
                                            <button
                                                type="button"
                                                wire:click="limparFiltros"
                                                class="mt-4 text-sm font-medium text-brand-600 hover:text-brand-700"
                                            >
                                                Limpar filtros
                                            </button>
                                        @else
                                            <p class="font-medium text-slate-900">Nenhum cliente cadastrado</p>
                                            <p class="mt-1 max-w-sm text-sm text-slate-600">
                                                Cadastre o primeiro cliente para vincular às ordens de serviço.
                                            </p>
                                            <button type="button" wire:click="create" class="btn-primary mt-4">
                                                <x-icon name="plus" class="h-4 w-4" />
                                                Novo Cliente
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if (count($clientesLista) > 0)
                        <tfoot class="border-t border-slate-100 bg-slate-50">
                            <tr>
                                <td colspan="5" class="px-4 py-3 text-sm text-slate-600">
                                    {{ count($clientesLista) }} {{ count($clientesLista) === 1 ? 'cliente exibido' : 'clientes exibidos' }}
                                    @if ($temFiltros)
                                        de {{ $totalClientes }} cadastrados
                                    @endif
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    @endif
</div>
