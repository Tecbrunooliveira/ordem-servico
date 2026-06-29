<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TarefaAnexo extends Model
{
    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    protected $table = 'anexos_tarefa';

    protected $fillable = [
        'tarefa_id',
        'nome_arquivo',
        'caminho',
        'tamanho_bytes',
        'tipo_mime',
    ];

    protected $hidden = [
        'caminho',
    ];

    protected $appends = [
        'url',
        'tamanho_formatado',
    ];

    public function tarefa(): BelongsTo
    {
        return $this->belongsTo(Tarefa::class, 'tarefa_id');
    }

    public function getUrlAttribute(): ?string
    {
        if (! $this->caminho) {
            return null;
        }

        return Storage::disk('public')->url($this->caminho);
    }

    public function getTamanhoFormatadoAttribute(): string
    {
        $bytes = (int) $this->tamanho_bytes;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', '.').' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1, ',', '.').' KB';
        }

        return $bytes.' B';
    }
}
