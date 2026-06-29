<?php

namespace App\Enums;

enum TarefaRecorrencia: string
{
    case Nenhuma = 'nenhuma';
    case Diaria = 'diaria';
    case Semanal = 'semanal';
    case Quinzenal = 'quinzenal';
    case Mensal = 'mensal';

    public function label(): string
    {
        return match ($this) {
            self::Nenhuma => 'Nenhuma',
            self::Diaria => 'Diária',
            self::Semanal => 'Semanal',
            self::Quinzenal => 'Quinzenal',
            self::Mensal => 'Mensal',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $recorrencia) => [$recorrencia->value => $recorrencia->label()])
            ->all();
    }
}
