<?php

namespace App\Enums;

enum TarefaPrioridade: string
{
    case Baixa = 'baixa';
    case Media = 'media';
    case Alta = 'alta';
    case Urgente = 'urgente';

    public function label(): string
    {
        return match ($this) {
            self::Baixa => 'Baixa',
            self::Media => 'Média',
            self::Alta => 'Alta',
            self::Urgente => 'Urgente',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Baixa => '#94a3b8',
            self::Media => '#3b82f6',
            self::Alta => '#f97316',
            self::Urgente => '#ef4444',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $prioridade) => [$prioridade->value => $prioridade->label()])
            ->all();
    }
}
