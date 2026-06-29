<?php

namespace App\Enums;

enum OrdemServicoTipo: string
{
    case Treinamento = 'treinamento';
    case VisitaTecnica = 'visita_tecnica';
    case Manutencao = 'manutencao';

    public function label(): string
    {
        return match ($this) {
            self::Treinamento => 'Treinamento',
            self::VisitaTecnica => 'Visita Técnica',
            self::Manutencao => 'Manutenção',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $tipo) => [$tipo->value => $tipo->label()])
            ->all();
    }

    public function color(): string
    {
        return match ($this) {
            self::Treinamento => '#3b82f6',
            self::VisitaTecnica => '#8b5cf6',
            self::Manutencao => '#f97316',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Treinamento => 'bg-blue-100 text-blue-800 ring-1 ring-blue-200',
            self::VisitaTecnica => 'bg-violet-100 text-violet-800 ring-1 ring-violet-200',
            self::Manutencao => 'bg-orange-100 text-orange-800 ring-1 ring-orange-200',
        };
    }
}
