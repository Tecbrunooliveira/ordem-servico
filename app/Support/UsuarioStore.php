<?php

namespace App\Support;

use Illuminate\Support\Facades\Hash;

class UsuarioStore
{
    private const SESSION_KEY = 'usuarios.cadastro';

    /** @return array<int, array<string, mixed>> */
    public static function defaults(): array
    {
        return [
            self::normalize([
                'id' => 1,
                'nome' => 'João Silva',
                'email' => 'joao.silva@gestaotecnica.com.br',
                'senha' => '123456',
                'tipo' => 'tecnico',
                'telefone' => '(11) 98765-4321',
                'clientes_ids' => [],
            ]),
            self::normalize([
                'id' => 2,
                'nome' => 'Marcos Oliveira',
                'email' => 'marcos@abc.com.br',
                'senha' => '123456',
                'tipo' => 'cliente',
                'telefone' => '(11) 91234-5678',
                'clientes_ids' => [1],
            ]),
            self::normalize([
                'id' => 3,
                'nome' => 'Ana Paula',
                'email' => 'ana.paula@gestaotecnica.com.br',
                'senha' => '123456',
                'tipo' => 'administrador',
                'telefone' => '(19) 99876-5432',
                'clientes_ids' => [],
            ]),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        $salvo = session(self::SESSION_KEY);

        if (! is_array($salvo) || $salvo === []) {
            return self::defaults();
        }

        return array_map([self::class, 'normalize'], $salvo);
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        foreach (self::all() as $usuario) {
            if (($usuario['id'] ?? null) === $id) {
                return $usuario;
            }
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    public static function findByEmail(string $email): ?array
    {
        $emailNormalizado = mb_strtolower(trim($email));

        foreach (self::all() as $usuario) {
            if (mb_strtolower($usuario['email'] ?? '') === $emailNormalizado) {
                return $usuario;
            }
        }

        return null;
    }

    /** @param  array<int, array<string, mixed>>  $usuarios */
    public static function saveAll(array $usuarios): void
    {
        session([self::SESSION_KEY => array_values(array_map([self::class, 'normalize'], $usuarios))]);
    }

    /** @param  array<string, mixed>  $usuario */
    private static function normalize(array $usuario): array
    {
        $senha = $usuario['senha'] ?? '';

        if ($senha !== '' && ! str_starts_with($senha, '$2y$') && ! str_starts_with($senha, '$2a$')) {
            $usuario['senha'] = Hash::make($senha);
        }

        return $usuario;
    }
}
