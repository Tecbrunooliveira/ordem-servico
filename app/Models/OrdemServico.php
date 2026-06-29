<?php

namespace App\Models;

use App\Enums\OrdemServicoStatus;
use App\Enums\OrdemServicoTipo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdemServico extends Model
{
    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    protected $table = 'ordens_servico';

    protected $fillable = [
        'cliente_id',
        'tecnico_id',
        'tipo',
        'titulo',
        'descricao',
        'data_agendada',
        'status',
        'participante',
        'participante_telefone',
        'tempo_segundos',
        'pausada',
        'descricao_servicos',
        'participante_1',
        'participante_2',
        'participante_3',
        'participante_4',
        'observacoes',
        'iniciada_em',
        'finalizada_em',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => OrdemServicoTipo::class,
            'status' => OrdemServicoStatus::class,
            'data_agendada' => 'date',
            'pausada' => 'boolean',
            'iniciada_em' => 'datetime',
            'finalizada_em' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'tecnico_id');
    }

    public function comentarios(): HasMany
    {
        return $this->hasMany(OrdemServicoComentario::class, 'ordem_servico_id');
    }

    public function pausas(): HasMany
    {
        return $this->hasMany(OrdemServicoPausa::class, 'ordem_servico_id');
    }

    /** @return array<int, string> */
    public function participantesLista(): array
    {
        return array_values(array_filter([
            $this->participante_1,
            $this->participante_2,
            $this->participante_3,
            $this->participante_4,
        ]));
    }
}
