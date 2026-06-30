<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'nome_empresa',
        'razao_social',
        'cnpj',
        'endereco',
        'cidade',
        'estado',
        'cep',
        'telefone',
        'email',
        'site',
        'caminho_logo',
    ];

    protected $hidden = [
        'caminho_logo',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->caminho_logo) {
            return null;
        }

        return asset('storage/'.$this->caminho_logo);
    }

    /** @return array<string, mixed> */
    public function toConfigArray(): array
    {
        return [
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
            'logo' => $this->logo_url,
        ];
    }
}
