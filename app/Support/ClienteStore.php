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

    /** @param  array<int, array<string, mixed>>  $clientes */
    public static function saveAll(array $clientes): void
    {
        DB::transaction(function () use ($clientes): void {
            $ids = [];

            foreach ($clientes as $cliente) {
                $attributes = self::toAttributes($cliente);

                if (! empty($cliente['id'])) {
                    $model = Cliente::query()->find($cliente['id']);
                    $model?->update($attributes);
                    $ids[] = (int) $cliente['id'];
                } else {
                    $model = Cliente::query()->create($attributes);
                    $ids[] = $model->id;
                }
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
        return [
            'nome' => $cliente['nome'],
            'documento' => $cliente['documento'] ?? null,
            'email' => $cliente['email'] ?? null,
            'telefone' => $cliente['telefone'] ?? null,
            'cidade' => $cliente['cidade'] ?? null,
            'estado' => $cliente['estado'] ?? null,
            'rua' => $cliente['rua'] ?? null,
            'numero' => $cliente['numero'] ?? null,
            'bairro' => $cliente['bairro'] ?? null,
            'cep' => $cliente['cep'] ?? null,
            'endereco' => $cliente['endereco'] ?? null,
            'ativo' => (bool) ($cliente['ativo'] ?? true),
        ];
    }
}
