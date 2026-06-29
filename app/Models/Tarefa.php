<?php

namespace App\Models;

use App\Enums\TarefaCategoria;
use App\Enums\TarefaPrioridade;
use App\Enums\TarefaRecorrencia;
use App\Enums\TarefaStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tarefa extends Model
{
    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'titulo',
        'descricao',
        'status',
        'prioridade',
        'data_vencimento',
        'responsavel_id',
        'categoria',
        'data_inicio',
        'tempo_segundos',
        'recorrencia',
    ];

    protected function casts(): array
    {
        return [
            'status' => TarefaStatus::class,
            'prioridade' => TarefaPrioridade::class,
            'categoria' => TarefaCategoria::class,
            'recorrencia' => TarefaRecorrencia::class,
            'data_vencimento' => 'date',
            'data_inicio' => 'date',
        ];
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'responsavel_id');
    }

    public function comentarios(): HasMany
    {
        return $this->hasMany(TarefaComentario::class, 'tarefa_id');
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(TarefaAnexo::class, 'tarefa_id');
    }
}
