<?php

namespace App\Support;

use App\Models\Empresa;
use Illuminate\Http\UploadedFile;
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
    public static function branding(): array
    {
        $empresa = self::get();
        $nome = trim((string) ($empresa['nome_empresa'] ?? '')) ?: (string) config('navigation.brand.name');
        $razao = trim((string) ($empresa['razao_social'] ?? '')) ?: $nome;
        $logo = $empresa['logo'] ?? null;

        return [
            'nome' => $nome,
            'razao_social' => $razao,
            'subtitulo' => $razao !== $nome ? $razao : (string) config('navigation.brand.subtitle'),
            'logo' => $logo,
            'tem_logo' => filled($logo),
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

    public static function logoUrl(?string $caminhoLogo): ?string
    {
        if (! filled($caminhoLogo)) {
            return null;
        }

        if (! Storage::disk('public')->exists($caminhoLogo)) {
            return null;
        }

        return Subdirectory::applicationUrl('/storage/'.$caminhoLogo);
    }

    public static function saveLogoFromUpload(UploadedFile $file): string
    {
        $empresa = Empresa::query()->firstOrCreate(['id' => 1], self::defaultsForModel());

        if ($empresa->caminho_logo) {
            Storage::disk('public')->delete($empresa->caminho_logo);
        }

        $filename = $file->store('empresa', 'public');

        $empresa->update(['caminho_logo' => $filename]);

        return self::logoUrl($filename) ?? Subdirectory::applicationUrl('/storage/'.$filename);
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
