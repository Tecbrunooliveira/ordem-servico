<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TarefaPausa extends Model
{
    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    protected $table = 'pausas_tarefa';

    protected $fillable = [
        'tarefa_id',
        'motivo',
        'pausada_em',
    ];

    protected function casts(): array
    {
        return [
            'pausada_em' => 'datetime',
        ];
    }

    public function tarefa(): BelongsTo
    {
        return $this->belongsTo(Tarefa::class, 'tarefa_id');
    }
}
