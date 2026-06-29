<?php

namespace App\Support;

use App\Models\Cliente;
use Illuminate\Support\Facades\DB;

class ClienteStore
{
    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        return Cliente::query()
            ->orderBy('nome')
            ->get()
            ->map(fn (Cliente $cliente) => self::fromModel($cliente))
            ->all();
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        $cliente = Cliente::query()->find($id);

        return $cliente ? self::fromModel($cliente) : null;
    }

    /** @param  array<string, mixed>  $cliente */
    public static function upsert(array $cliente): Cliente
    {
        $attributes = self::toAttributes($cliente);

        if (! empty($cliente['id'])) {
            $model = Cliente::query()->find($cliente['id']);

            if ($model) {
                $model->update($attributes);

                return $model;
            }
        }

        return Cliente::query()->create($attributes);
    }

    public static function delete(int $id): void
    {
        Cliente::query()->whereKey($id)->delete();
    }

    /** @param  array<int, array<string, mixed>>  $clientes */
    public static function saveAll(array $clientes): void
    {
        DB::transaction(function () use ($clientes): void {
            $ids = [];

            foreach ($clientes as $cliente) {
                $ids[] = self::upsert($cliente)->id;
            }

            if ($ids !== []) {
                Cliente::query()->whereNotIn('id', $ids)->delete();
            }
        });
    }

    /** @param  array<string, mixed>  $cliente */
    public static function enderecoCompleto(array $cliente): string
    {
        $partes = array_filter([
            trim(($cliente['rua'] ?? '').($cliente['numero'] ? ', '.$cliente['numero'] : '')),
            $cliente['bairro'] ?? '',
            trim(($cliente['cidade'] ?? '').($cliente['estado'] ? ' — '.$cliente['estado'] : '')),
        ]);

        if ($partes !== []) {
            return implode(' — ', $partes);
        }

        return ($cliente['endereco'] ?? '') ?: '—';
    }

    /** @return array<string, mixed> */
    private static function fromModel(Cliente $cliente): array
    {
        return [
            'id' => $cliente->id,
            'nome' => $cliente->nome,
            'documento' => $cliente->documento,
            'email' => $cliente->email,
            'telefone' => $cliente->telefone,
            'cidade' => $cliente->cidade,
            'estado' => $cliente->estado,
            'rua' => $cliente->rua,
            'numero' => $cliente->numero,
            'bairro' => $cliente->bairro,
            'cep' => $cliente->cep,
            'endereco' => $cliente->endereco,
            'ativo' => $cliente->ativo,
        ];
    }

    /** @param  array<string, mixed>  $cliente */
    private static function toAttributes(array $cliente): array
    {
        $nullable = static fn (mixed $value): mixed => is_string($value) && trim($value) === '' ? null : $value;

        return [
            'nome' => $cliente['nome'],
            'documento' => $nullable($cliente['documento'] ?? null),
            'email' => $nullable($cliente['email'] ?? null),
            'telefone' => $nullable($cliente['telefone'] ?? null),
            'cidade' => $nullable($cliente['cidade'] ?? null),
            'estado' => $nullable($cliente['estado'] ?? null),
            'rua' => $nullable($cliente['rua'] ?? null),
            'numero' => $nullable($cliente['numero'] ?? null),
            'bairro' => $nullable($cliente['bairro'] ?? null),
            'cep' => $nullable($cliente['cep'] ?? null),
            'endereco' => $nullable($cliente['endereco'] ?? null),
            'ativo' => (bool) ($cliente['ativo'] ?? true),
        ];
    }
}
