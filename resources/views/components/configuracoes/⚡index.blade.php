<?php

use App\Support\EmpresaConfig;
use Livewire\Component;
use Livewire\WithFileUploads;
use WireUi\Traits\WireUiActions;

new class extends Component
{
    use WireUiActions;
    use WithFileUploads;

    public string $abaAtiva = 'empresa';

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

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $logo = null;

    public ?string $logoPreview = null;

    /** @var array<int, string> */
    public array $estados = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
        'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
        'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
    ];

    /** @var array<int, array{label: string, value: string}> */
    public array $abas = [
        ['label' => 'Empresa', 'value' => 'empresa', 'icon' => 'building-office'],
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
            'logo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function mount(): void
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

    public function setAba(string $aba): void
    {
        if (collect($this->abas)->contains(fn (array $item) => $item['value'] === $aba)) {
            $this->abaAtiva = $aba;
        }
    }

    public function updatedLogo(): void
    {
        $this->validateOnly('logo', $this->rulesEmpresa());
        $this->persistLogoPreview();
    }

    public function removerLogo(): void
    {
        $this->logo = null;
        $this->logoPreview = null;
        EmpresaConfig::removeLogo();

        $this->notification()->send([
            'icon' => 'success',
            'title' => 'Logo removido',
            'timeout' => 3000,
        ]);
    }

    public function salvarEmpresa(): void
    {
        $this->validate($this->rulesEmpresa());

        $this->persistLogoPreview();
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

        $this->notification()->success('Configurações salvas', 'Os dados da empresa foram atualizados.');
    }

    private function persistLogoPreview(): void
    {
        if (! $this->logo) {
            return;
        }

        $extension = $this->logo->getClientOriginalExtension() ?: 'png';
        $this->logoPreview = EmpresaConfig::saveLogoFromUpload(
            $this->logo->getRealPath(),
            $extension,
        );
        $this->logo = null;
    }
};
?>

<div>
    <div class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <nav class="flex gap-1 overflow-x-auto p-2" aria-label="Abas de configuração">
            @foreach ($abas as $aba)
                <button
                    type="button"
                    wire:click="setAba('{{ $aba['value'] }}')"
                    @class([
                        'inline-flex shrink-0 items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium transition-colors',
                        'bg-brand-500 text-white shadow-sm' => $abaAtiva === $aba['value'],
                        'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => $abaAtiva !== $aba['value'],
                    ])
                >
                    <x-icon :name="$aba['icon']" class="h-4 w-4" />
                    {{ $aba['label'] }}
                </button>
            @endforeach
        </nav>
    </div>

    @if ($abaAtiva === 'empresa')
        <form wire:submit="salvarEmpresa" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-slate-900">Dados da empresa</h2>
                <p class="text-sm text-slate-600">Informações exibidas em documentos, relatórios e comunicações do sistema.</p>
            </div>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-input wire:model="nome_empresa" label="Nome da empresa" placeholder="Ex: Gestão Técnica" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input wire:model="razao_social" label="Razão social" placeholder="Ex: Gestão Técnica Serviços Ltda" />
                        </div>

                        <x-maskable
                            wire:model="cnpj"
                            label="CNPJ"
                            mask="##.###.###/####-##"
                            emit-formatted
                            placeholder="00.000.000/0001-00"
                        />

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

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Logo</label>
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
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

                        <input
                            type="file"
                            wire:model="logo"
                            accept="image/*"
                            class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-500 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-brand-600"
                        >

                        <p class="mt-2 text-xs text-slate-500">PNG, JPG ou SVG. Máximo 2 MB.</p>

                        <div wire:loading wire:target="logo" class="mt-2 text-xs text-brand-600">
                            Carregando logo...
                        </div>

                        @if ($logoPreview)
                            <button
                                type="button"
                                wire:click="removerLogo"
                                class="mt-3 text-sm font-medium text-red-600 hover:text-red-700"
                            >
                                Remover logo
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end border-t border-slate-100 pt-5">
                <x-button primary type="submit" icon="check" label="Salvar configurações" />
            </div>
        </form>
    @endif
</div>
