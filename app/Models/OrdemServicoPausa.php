<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdemServicoPausa extends Model
{
    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    protected $table = 'pausas_ordem_servico';

    protected $fillable = [
        'ordem_servico_id',
        'motivo',
        'pausada_em',
    ];

    protected function casts(): array
    {
        return [
            'pausada_em' => 'datetime',
        ];
    }

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class, 'ordem_servico_id');
    }
}
