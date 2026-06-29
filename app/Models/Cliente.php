<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'nome',
        'documento',
        'email',
        'telefone',
        'cidade',
        'estado',
        'endereco',
        'rua',
        'numero',
        'bairro',
        'cep',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'cliente_usuario', 'cliente_id', 'usuario_id')
            ->withTimestamps(Usuario::CREATED_AT, Usuario::UPDATED_AT);
    }

    public function ordensServico(): HasMany
    {
        return $this->hasMany(OrdemServico::class);
    }

    public function enderecoCompleto(): string
    {
        $partes = array_filter([
            trim(($this->rua ?? '').($this->numero ? ', '.$this->numero : '')),
            $this->bairro,
            trim(($this->cidade ?? '').($this->estado ? ' — '.$this->estado : '')),
        ]);

        if ($partes !== []) {
            return implode(' — ', $partes);
        }

        return $this->endereco ?: '—';
    }
}
