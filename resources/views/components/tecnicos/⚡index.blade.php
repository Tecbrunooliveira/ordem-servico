<?php

use App\Enums\UsuarioTipo;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

new class extends Component
{
    use WireUiActions;

    public bool $showForm = false;

    public ?int $editingId = null;

    /** @var array<int, array<string, mixed>> */
    public array $tecnicos = [];

    public string $nome = '';

    public string $email = '';

    public string $telefone = '';

    public string $busca = '';

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
            'telefone' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function mount(): void
    {
        $this->carregarTecnicos();
    }

    private function carregarTecnicos(): void
    {
        $this->tecnicos = Usuario::query()
            ->tecnicos()
            ->orderBy('nome')
            ->get(['id', 'nome', 'email', 'telefone'])
            ->map(fn (Usuario $usuario) => [
                'id' => $usuario->id,
                'nome' => $usuario->nome,
                'email' => $usuario->email,
                'telefone' => $usuario->telefone ?? '',
            ])
            ->all();
    }

    /** @return array<string, mixed> */
    private function findTecnico(int $id): array
    {
        foreach ($this->tecnicos as $tecnico) {
            if ($tecnico['id'] === $id) {
                return $tecnico;
            }
        }

        throw new \RuntimeException('Técnico não encontrado.');
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $tecnico = $this->findTecnico($id);

        $this->editingId = $tecnico['id'];
        $this->nome = $tecnico['nome'];
        $this->email = $tecnico['email'];
        $this->telefone = $tecnico['telefone'];
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            Usuario::query()->whereKey($this->editingId)->update([
                'nome' => $data['nome'],
                'email' => $data['email'],
                'telefone' => $data['telefone'],
            ]);

            $this->notification()->success('Técnico atualizado', 'As alterações foram salvas.');
        } else {
            Usuario::query()->create([
                'nome' => $data['nome'],
                'email' => $data['email'],
                'telefone' => $data['telefone'],
                'senha' => Hash::make(Str::password(12)),
                'tipo' => UsuarioTipo::Tecnico,
                'ativo' => true,
            ]);

            $this->notification()->success('Técnico cadastrado', 'O membro da equipe foi adicionado.');
        }

        $this->carregarTecnicos();
        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(int $id): void
    {
        if ($id === auth()->id()) {
            $this->notification()->error('Ação não permitida', 'Você não pode excluir o próprio usuário logado.');

            return;
        }

        Usuario::query()->whereKey($id)->where('tipo', UsuarioTipo::Tecnico)->delete();
        $this->carregarTecnicos();

        $this->notification()->success('Técnico removido', 'O registro foi excluído.');
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'nome', 'email', 'telefone']);
        $this->resetValidation();
    }

    /** @return array<int, array<string, mixed>> */
    private function tecnicosFiltrados(): array
    {
        $busca = mb_strtolower(trim($this->busca));

        return collect($this->tecnicos)
            ->filter(function (array $tecnico) use ($busca): bool {
                if ($busca === '') {
                    return true;
                }

                return str_contains(mb_strtolower($tecnico['nome']), $busca)
                    || str_contains(mb_strtolower($tecnico['email']), $busca);
            })
            ->values()
            ->all();
    }

    public function with(): array
    {
        return [
            'tecnicosLista' => $this->tecnicosFiltrados(),
            'totalTecnicos' => count($this->tecnicos),
        ];
    }
};
?>

<div>
    @if ($showForm)
        <div class="mx-auto max-w-xl">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-slate-900">
                    {{ $editingId ? 'Editar Técnico' : 'Novo Técnico' }}
                </h2>
                <p class="text-sm text-slate-600">Cadastre o membro da equipe técnica com e-mail de acesso.</p>
            </div>

            <form wire:submit="save" class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <x-input wire:model="nome" label="Nome" placeholder="Ex: João Silva" />
                <x-input wire:model="email" label="E-mail" type="email" placeholder="tecnico@empresa.com.br" />
                <x-input wire:model="telefone" label="Telefone" placeholder="(11) 98765-4321" />

                <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                    <x-button flat label="Cancelar" wire:click="cancel" />
                    <x-button primary type="submit" label="{{ $editingId ? 'Salvar alterações' : 'Cadastrar técnico' }}" />
                </div>
            </form>
        </div>
    @else
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="w-full sm:max-w-md">
                <x-input
                    wire:model.live.debounce.300ms="busca"
                    icon="magnifying-glass"
                    label="Buscar"
                    placeholder="Nome ou e-mail"
                />
            </div>
            <x-button primary icon="plus" label="Novo Técnico" wire:click="create" />
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-5 py-3">Nome</th>
                            <th class="px-5 py-3">E-mail</th>
                            <th class="px-5 py-3">Telefone</th>
                            <th class="px-5 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($tecnicosLista as $tecnico)
                            <tr wire:key="tecnico-{{ $tecnico['id'] }}" class="hover:bg-slate-50">
                                <td class="px-5 py-4 font-medium text-slate-900">{{ $tecnico['nome'] }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $tecnico['email'] }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $tecnico['telefone'] ?: '—' }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <x-mini-button icon="pencil" wire:click="edit({{ $tecnico['id'] }})" />
                                        <x-mini-button
                                            icon="trash"
                                            negative
                                            wire:click="delete({{ $tecnico['id'] }})"
                                            wire:confirm="Deseja excluir este técnico?"
                                        />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-12 text-center text-slate-600">
                                    @if ($busca)
                                        Nenhum técnico encontrado para "{{ $busca }}".
                                    @else
                                        Nenhum técnico cadastrado. Clique em "Novo Técnico" para começar.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t border-slate-100 bg-slate-50">
                        <tr>
                            <td colspan="4" class="px-5 py-3 text-sm text-slate-600">
                                @if ($busca)
                                    Exibindo {{ count($tecnicosLista) }} de {{ $totalTecnicos }} {{ $totalTecnicos === 1 ? 'técnico cadastrado' : 'técnicos cadastrados' }}
                                @else
                                    {{ $totalTecnicos }} {{ $totalTecnicos === 1 ? 'técnico cadastrado' : 'técnicos cadastrados' }}
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
</div>
