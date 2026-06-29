<?php

namespace App\Models;

use App\Enums\UsuarioTipo;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['nome', 'email', 'senha', 'telefone', 'tipo', 'ativo'])]
#[Hidden(['senha', 'token_lembrar'])]
class Usuario extends Authenticatable
{
    /** @use HasFactory<UsuarioFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $table = 'usuarios';

    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    protected function casts(): array
    {
        return [
            'email_verificado_em' => 'datetime',
            'senha' => 'hashed',
            'tipo' => UsuarioTipo::class,
            'ativo' => 'boolean',
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'senha';
    }

    public function getRememberTokenName(): string
    {
        return 'token_lembrar';
    }

    /** @return Collection<int, string> */
    public function getRoleNames(): Collection
    {
        return collect([$this->tipo?->label() ?? 'Usuário']);
    }

    public function can($abilities, $arguments = []): bool
    {
        if ($this->tipo === UsuarioTipo::Administrador) {
            return true;
        }

        if ($this->tipo === UsuarioTipo::Cliente) {
            return false;
        }

        return $this->tipo === UsuarioTipo::Tecnico;
    }

    public function clientes(): BelongsToMany
    {
        return $this->belongsToMany(Cliente::class, 'cliente_usuario', 'usuario_id', 'cliente_id')
            ->withTimestamps(self::CREATED_AT, self::UPDATED_AT);
    }

    public function ordensServico(): HasMany
    {
        return $this->hasMany(OrdemServico::class, 'tecnico_id');
    }

    public function tarefasResponsavel(): HasMany
    {
        return $this->hasMany(Tarefa::class, 'responsavel_id');
    }

    public function comentariosOrdemServico(): HasMany
    {
        return $this->hasMany(OrdemServicoComentario::class, 'usuario_id');
    }

    public function comentariosTarefa(): HasMany
    {
        return $this->hasMany(TarefaComentario::class, 'usuario_id');
    }

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    public function scopeTecnicos($query)
    {
        return $query->where('tipo', UsuarioTipo::Tecnico);
    }
}
