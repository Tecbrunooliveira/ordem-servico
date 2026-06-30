<?php

namespace App\Support;

use App\Enums\UsuarioTipo;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ClienteAccess
{
    /** @var array<int, string> */
    private const PERMISSOES_LEITURA = [
        'tarefas.view',
        'ordem-servico.view',
    ];

    public static function usuarioEhCliente(?Usuario $usuario = null): bool
    {
        $usuario ??= auth()->user();

        return $usuario instanceof Usuario
            && $usuario->tipo === UsuarioTipo::Cliente;
    }

    public static function somenteLeitura(?Usuario $usuario = null): bool
    {
        return self::usuarioEhCliente($usuario);
    }

    public static function pode(string $permissao, ?Usuario $usuario = null): bool
    {
        $usuario ??= auth()->user();

        if (! $usuario instanceof Usuario) {
            return false;
        }

        if ($usuario->tipo === UsuarioTipo::Administrador) {
            return true;
        }

        if ($usuario->tipo === UsuarioTipo::Cliente) {
            return in_array($permissao, self::PERMISSOES_LEITURA, true);
        }

        return $usuario->tipo === UsuarioTipo::Tecnico;
    }

    /** @return Collection<int, int> */
    public static function clienteIds(?Usuario $usuario = null): Collection
    {
        $usuario ??= auth()->user();

        if (! $usuario instanceof Usuario) {
            return collect();
        }

        return $usuario->clientes()->pluck('clientes.id');
    }

    /** @param  Builder<\Illuminate\Database\Eloquent\Model>  $query */
    public static function aplicarFiltroCliente(Builder $query, string $coluna = 'cliente_id'): Builder
    {
        if (! self::usuarioEhCliente()) {
            return $query;
        }

        if (! Schema::hasColumn($query->getModel()->getTable(), $coluna)) {
            return $query->whereRaw('1 = 0');
        }

        $ids = self::clienteIds();

        if ($ids->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($coluna, $ids);
    }
}
