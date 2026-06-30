<?php

use App\Support\EmpresaConfig;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

new class extends Component
{
    use WireUiActions;

    public ?string $secaoEditando = null;

    public string $nome_empresa = '';

    public string $razao_social = '';

    public string $cnpj = '';

    public string $endereco = '';

    public string $cidade = '';

    public string $estado = '';

    public string $cep = '';

    public string $telefone = '';

    public string $email = '';

    public string $site = '';

    public ?string $logoPreview = null;

    public ?string $feedbackSucesso = null;

    public ?string $feedbackErro = null;

    /** @var array<int, string> */
    public array $estados = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
        'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
        'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
    ];

    /** @var array<int, array{label: string, value: string, icon: string, descricao: string}> */
    public array $secoes = [
        [
            'label' => 'Empresa',
            'value' => 'empresa',
            'icon' => 'building-office-2',
            'descricao' => 'Nome, CNPJ, contato, endereço e logo exibidos em documentos e no sistema.',
        ],
    ];

    protected function rulesEmpresa(): array
    {
        return [
            'nome_empresa' => ['nullable', 'string', 'max:255'],
            'razao_social' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:20'],
            'endereco' => ['nullable', 'string', 'max:500'],
            'cidade' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'string', 'max:2'],
            'cep' => ['nullable', 'string', 'max:10'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'site' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function mount(): void
    {
        if (request('editar') === 'empresa') {
            $this->secaoEditando = 'empresa';
        }

        $this->carregarEmpresa();

        if (session('logo_ok')) {
            $this->feedbackSucesso = (string) session('logo_ok');
        }

        if (session('logo_erro')) {
            $this->feedbackErro = (string) session('logo_erro');
        }
    }

    public function editarSecao(string $secao): void
    {
        if (! collect($this->secoes)->contains(fn (array $item) => $item['value'] === $secao)) {
            return;
        }

        $this->secaoEditando = $secao;
        $this->feedbackSucesso = null;
        $this->feedbackErro = null;
    }

    public function voltarLista(): void
    {
        $this->secaoEditando = null;
        $this->feedbackSucesso = null;
        $this->feedbackErro = null;
        $this->resetValidation();
    }

    /** @return array<string, string|null> */
    public function resumoEmpresa(): array
    {
        $nome = trim($this->razao_social) ?: trim($this->nome_empresa) ?: 'Não configurado';
        $local = trim(collect([$this->cidade, $this->estado])->filter()->implode(' / '));

        return [
            'nome' => $nome,
            'cnpj' => trim($this->cnpj) ?: '—',
            'local' => $local !== '' ? $local : '—',
            'contato' => trim($this->email) ?: trim($this->telefone) ?: '—',
        ];
    }

    /** @param  array<string, string>  $dados */
    public function aplicarDadosCnpjEmpresa(array $dados): void
    {
        $dados = validator($dados, [
            'cnpj' => ['nullable', 'string', 'max:20'],
            'razao_social' => ['nullable', 'string', 'max:255'],
            'nome_empresa' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'endereco' => ['nullable', 'string', 'max:500'],
            'cidade' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'string', 'max:2'],
            'cep' => ['nullable', 'string', 'max:10'],
        ])->validate();

        $this->cnpj = $dados['cnpj'] ?? $this->cnpj;
        $this->razao_social = $dados['razao_social'] ?? '';
        $this->nome_empresa = $dados['nome_empresa'] ?? '';
        $this->email = $dados['email'] ?? '';
        $this->telefone = $dados['telefone'] ?? '';
        $this->endereco = $dados['endereco'] ?? '';
        $this->cidade = $dados['cidade'] ?? '';
        $this->estado = strtoupper($dados['estado'] ?? '');
        $this->cep = $dados['cep'] ?? '';
    }

    public function removerLogo(): void
    {
        $this->logoPreview = null;
        EmpresaConfig::removeLogo();

        $this->feedbackErro = null;
        $this->feedbackSucesso = 'Logo removida com sucesso.';
    }

    public function salvarEmpresa(): void
    {
        $this->validate($this->rulesEmpresa());
        $this->estado = strtoupper($this->estado);

        EmpresaConfig::save([
            'nome_empresa' => $this->nome_empresa,
            'razao_social' => $this->razao_social,
            'cnpj' => $this->cnpj,
            'endereco' => $this->endereco,
            'cidade' => $this->cidade,
            'estado' => $this->estado,
            'cep' => $this->cep,
            'telefone' => $this->telefone,
            'email' => $this->email,
            'site' => $this->site,
        ]);

        $this->feedbackErro = null;
        $this->feedbackSucesso = 'Os dados da empresa foram atualizados.';
        $this->notification()->success('Configurações salvas', $this->feedbackSucesso);
        $this->carregarEmpresa();
    }

    private function carregarEmpresa(): void
    {
        $empresa = EmpresaConfig::get();

        $this->nome_empresa = $empresa['nome_empresa'] ?? '';
        $this->razao_social = $empresa['razao_social'] ?? '';
        $this->cnpj = $empresa['cnpj'] ?? '';
        $this->endereco = $empresa['endereco'] ?? '';
        $this->cidade = $empresa['cidade'] ?? '';
        $this->estado = $empresa['estado'] ?? '';
        $this->cep = $empresa['cep'] ?? '';
        $this->telefone = $empresa['telefone'] ?? '';
        $this->email = $empresa['email'] ?? '';
        $this->site = $empresa['site'] ?? '';
        $this->logoPreview = $empresa['logo'] ?? null;
    }
};
?>

<div>
    @if ($secaoEditando === 'empresa')
        <div class="mb-4 sm:mb-6">
            <button
                type="button"
                wire:click="voltarLista"
                class="app-touch-target mb-4 inline-flex items-center gap-2 text-sm font-medium text-slate-600 transition hover:text-[#004200]"
            >
                <x-icon name="arrow-left" class="h-4 w-4" />
                Voltar para configurações
            </button>

            <h2 class="text-lg font-semibold text-slate-900 sm:text-xl">Dados da empresa</h2>
            <p class="mt-1 text-sm text-slate-600">Informações exibidas em documentos, relatórios e comunicações do sistema.</p>
        </div>

        @if ($feedbackSucesso)
            <div class="app-flash app-flash--success mb-4" role="status">
                <x-icon name="check-circle" class="app-flash__icon" />
                <p>{{ $feedbackSucesso }}</p>
            </div>
        @endif

        @if ($feedbackErro)
            <div class="app-flash app-flash--error mb-4" role="alert">
                <x-icon name="exclamation-circle" class="app-flash__icon" />
                <p>{{ $feedbackErro }}</p>
            </div>
        @endif

        @if ($errors->has('logo'))
            <div class="app-flash app-flash--error mb-4" role="alert">
                <x-icon name="exclamation-circle" class="app-flash__icon" />
                <p>{{ $errors->first('logo') }}</p>
            </div>
        @endif

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <form id="empresa-dados-form" wire:submit="salvarEmpresa">
                <div class="grid grid-cols-1 gap-5">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-cnpj-lookup-field
                                wire-model="cnpj"
                                apply-method="aplicarDadosCnpjEmpresa"
                                variant="empresa"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input wire:model="nome_empresa" label="Nome da empresa" placeholder="Ex: Gestão Técnica" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input wire:model="razao_social" label="Razão social" placeholder="Ex: Gestão Técnica Serviços Ltda" />
                        </div>

                        <x-phone
                            wire:model="telefone"
                            label="Telefone"
                            :mask="['(##) ####-####', '(##) #####-####']"
                            emit-formatted
                            placeholder="(11) 99999-9999"
                        />

                        <x-input wire:model="email" label="E-mail" type="email" placeholder="contato@empresa.com.br" />

                        <x-input wire:model="site" label="Site" placeholder="https://www.empresa.com.br" />

                        <div class="sm:col-span-2">
                            <x-input wire:model="endereco" label="Endereço" placeholder="Rua, número, bairro" />
                        </div>

                        <x-input wire:model="cidade" label="Cidade" placeholder="São Paulo" />

                        <x-native-select wire:model="estado" label="Estado">
                            <option value="">Selecione</option>
                            @foreach ($estados as $uf)
                                <option value="{{ $uf }}">{{ $uf }}</option>
                            @endforeach
                        </x-native-select>

                        <x-maskable
                            wire:model="cep"
                            label="CEP"
                            mask="#####-###"
                            emit-formatted
                            placeholder="00000-000"
                        />
                    </div>
                </div>
            </form>

            <form
                action="{{ route('configuracoes.empresa.logo') }}"
                method="POST"
                enctype="multipart/form-data"
                class="mt-5 border-t border-slate-100 pt-5"
            >
                @csrf

                <label class="mb-2 block text-sm font-medium text-gray-700">Logo</label>
                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3 sm:p-4">
                    <div class="mb-4 flex min-h-[8rem] items-center justify-center rounded-lg border border-slate-200 bg-white p-4">
                        @if ($logoPreview)
                            <img
                                src="{{ $logoPreview }}"
                                alt="Logo da empresa"
                                class="max-h-32 max-w-full object-contain"
                            >
                        @else
                            <div class="text-center">
                                <x-icon name="photo" class="mx-auto h-10 w-10 text-slate-300" />
                                <p class="mt-2 text-sm text-slate-500">Nenhuma logo enviada</p>
                            </div>
                        @endif
                    </div>

                    <label class="app-file-upload">
                        <span class="app-action-btn app-action-btn--secondary w-full sm:w-auto">Selecionar imagem</span>
                        <input
                            type="file"
                            name="logo"
                            accept="image/png,image/jpeg,image/webp,image/svg+xml"
                            class="sr-only"
                            onchange="if (this.files.length) this.form.submit()"
                        >
                    </label>

                    <p class="mt-2 text-xs leading-relaxed text-slate-500">
                        PNG, JPG ou SVG. Máximo 5 MB. Recomendado: 240×80 px (horizontal) ou 128×128 px (quadrada), fundo transparente.
                    </p>

                    @if ($logoPreview)
                        <button
                            type="button"
                            wire:click="removerLogo"
                            class="app-touch-target mt-3 text-sm font-medium text-red-600 hover:text-red-700"
                        >
                            Remover logo
                        </button>
                    @endif
                </div>
            </form>

            <div class="app-action-bar mt-6 border-t border-slate-100 pt-5">
                <button
                    type="button"
                    wire:click="voltarLista"
                    class="app-action-btn app-action-btn--secondary"
                >
                    Cancelar
                </button>
                <button
                    type="submit"
                    form="empresa-dados-form"
                    class="app-action-btn app-action-btn--primary"
                >
                    Salvar configurações
                </button>
            </div>
        </div>
    @else
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-slate-900">Configurações</h2>
            <p class="text-sm text-slate-600">Selecione uma opção para visualizar ou alterar.</p>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <ul class="divide-y divide-slate-100">
                @foreach ($secoes as $secao)
                    @php
                        $resumo = $secao['value'] === 'empresa' ? $this->resumoEmpresa() : [];
                    @endphp
                    <li wire:key="config-secao-{{ $secao['value'] }}">
                        <button
                            type="button"
                            wire:click="editarSecao('{{ $secao['value'] }}')"
                            class="app-touch-target flex w-full items-start gap-4 px-4 py-4 text-left transition hover:bg-[#005300]/5 sm:px-5"
                        >
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#005300]/10 text-[#005300] ring-1 ring-[#005300]/15">
                                <x-icon :name="$secao['icon']" class="h-5 w-5" />
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="flex items-start justify-between gap-3">
                                    <span>
                                        <span class="block text-sm font-semibold text-slate-900">{{ $secao['label'] }}</span>
                                        <span class="mt-1 block text-sm text-slate-500">{{ $secao['descricao'] }}</span>
                                    </span>
                                    <x-icon name="chevron-right" class="mt-0.5 h-5 w-5 shrink-0 text-slate-400" />
                                </span>

                                @if ($secao['value'] === 'empresa')
                                    <span class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                                        <span><span class="font-medium text-slate-700">{{ $resumo['nome'] }}</span></span>
                                        <span>CNPJ: {{ $resumo['cnpj'] }}</span>
                                        <span>{{ $resumo['local'] }}</span>
                                        <span>{{ $resumo['contato'] }}</span>
                                    </span>
                                @endif
                            </span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
