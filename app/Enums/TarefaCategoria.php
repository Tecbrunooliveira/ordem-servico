<?php

namespace App\Enums;

enum TarefaCategoria: string
{
    case Operacional = 'operacional';
    case Tecnica = 'tecnica';
    case Administrativa = 'administrativa';
    case Comercial = 'comercial';

    public function label(): string
    {
        return match ($this) {
            self::Operacional => 'Operacional',
            self::Tecnica => 'Técnica',
            self::Administrativa => 'Administrativa',
            self::Comercial => 'Comercial',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $categoria) => [$categoria->value => $categoria->label()])
            ->all();
    }
}
