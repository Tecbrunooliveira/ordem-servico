<?php

namespace App\Enums;

enum UsuarioTipo: string
{
    case Administrador = 'administrador';
    case Tecnico = 'tecnico';
    case Cliente = 'cliente';

    public function label(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Tecnico => 'Técnico',
            self::Cliente => 'Cliente',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Administrador => 'bg-violet-100 text-violet-800 ring-1 ring-violet-200',
            self::Tecnico => 'bg-blue-100 text-blue-800 ring-1 ring-blue-200',
            self::Cliente => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200',
        };
    }

    /** Acesso previsto para regras de permissão futuras. */
    public function descricaoAcesso(): string
    {
        return match ($this) {
            self::Administrador => 'Acesso completo ao sistema.',
            self::Tecnico => 'Acesso operacional conforme permissões da equipe.',
            self::Cliente => 'Acesso somente a Tarefas e Ordens de Serviço, sem editar ou excluir.',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $tipo) => [$tipo->value => $tipo->label()])
            ->all();
    }
}
