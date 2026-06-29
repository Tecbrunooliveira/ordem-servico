<?php

namespace App\Enums;

enum OrdemServicoStatus: string
{
    case Pendente = 'pendente';
    case EmAndamento = 'em_andamento';
    case Concluida = 'concluida';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::EmAndamento => 'Em'."\u{00A0}".'andamento',
            self::Concluida => 'Concluída',
            self::Cancelada => 'Cancelada',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pendente => 'bg-amber-100 text-amber-800 ring-1 ring-amber-200',
            self::EmAndamento => 'bg-blue-100 text-blue-800 ring-1 ring-blue-200',
            self::Concluida => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200',
            self::Cancelada => 'bg-red-100 text-red-800 ring-1 ring-red-200',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendente => '#f59e0b',
            self::EmAndamento => '#3b82f6',
            self::Concluida => '#10b981',
            self::Cancelada => '#ef4444',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->all();
    }
}
