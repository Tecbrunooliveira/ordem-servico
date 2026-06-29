<?php

namespace App\Support;

use App\Models\Empresa;
use Illuminate\Support\Facades\Storage;

class EmpresaConfig
{
    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'nome_empresa' => 'Gestão Técnica',
            'razao_social' => 'Gestão Técnica Serviços Ltda',
            'cnpj' => '12.345.678/0001-90',
            'endereco' => 'Av. Paulista, 1000 — Bela Vista',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'cep' => '01310-100',
            'telefone' => '(11) 3456-7890',
            'email' => 'contato@gestaotecnica.com.br',
            'site' => 'https://www.gestaotecnica.com.br',
            'logo' => null,
        ];
    }

    /** @return array<string, mixed> */
    public static function get(): array
    {
        $empresa = Empresa::query()->first();

        if (! $empresa) {
            return self::defaults();
        }

        return array_merge(self::defaults(), $empresa->toConfigArray());
    }

    /** @param  array<string, mixed>  $dados */
    public static function save(array $dados): void
    {
        $empresa = Empresa::query()->firstOrCreate(['id' => 1], self::defaultsForModel());

        $empresa->fill([
            'nome_empresa' => $dados['nome_empresa'] ?? $empresa->nome_empresa,
            'razao_social' => $dados['razao_social'] ?? $empresa->razao_social,
            'cnpj' => $dados['cnpj'] ?? $empresa->cnpj,
            'endereco' => $dados['endereco'] ?? $empresa->endereco,
            'cidade' => $dados['cidade'] ?? $empresa->cidade,
            'estado' => $dados['estado'] ?? $empresa->estado,
            'cep' => $dados['cep'] ?? $empresa->cep,
            'telefone' => $dados['telefone'] ?? $empresa->telefone,
            'email' => $dados['email'] ?? $empresa->email,
            'site' => $dados['site'] ?? $empresa->site,
        ])->save();
    }

    public static function saveLogoFromUpload(string $tempPath, string $extension): string
    {
        $empresa = Empresa::query()->firstOrCreate(['id' => 1], self::defaultsForModel());

        if ($empresa->caminho_logo) {
            Storage::disk('public')->delete($empresa->caminho_logo);
        }

        $filename = 'empresa/logo-'.time().'.'.$extension;
        Storage::disk('public')->put($filename, file_get_contents($tempPath));

        $empresa->update(['caminho_logo' => $filename]);

        return Storage::disk('public')->url($filename);
    }

    public static function removeLogo(): void
    {
        $empresa = Empresa::query()->first();

        if (! $empresa?->caminho_logo) {
            return;
        }

        Storage::disk('public')->delete($empresa->caminho_logo);
        $empresa->update(['caminho_logo' => null]);
    }

    /** @return array<string, mixed> */
    private static function defaultsForModel(): array
    {
        $defaults = self::defaults();

        unset($defaults['logo']);

        return $defaults;
    }
}
