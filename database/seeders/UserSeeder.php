<?php

namespace Database\Seeders;

use App\Enums\UsuarioTipo;
use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        Usuario::query()->updateOrCreate(
            ['email' => 'joao.silva@gestaotecnica.com.br'],
            [
                'nome' => 'João Silva',
                'senha' => '123456',
                'tipo' => UsuarioTipo::Tecnico,
                'telefone' => '(11) 98765-4321',
                'ativo' => true,
            ],
        );

        $marcos = Usuario::query()->updateOrCreate(
            ['email' => 'marcos@abc.com.br'],
            [
                'nome' => 'Marcos Oliveira',
                'senha' => '123456',
                'tipo' => UsuarioTipo::Cliente,
                'telefone' => '(11) 91234-5678',
                'ativo' => true,
            ],
        );

        Usuario::query()->updateOrCreate(
            ['email' => 'ana.paula@gestaotecnica.com.br'],
            [
                'nome' => 'Ana Paula',
                'senha' => '123456',
                'tipo' => UsuarioTipo::Administrador,
                'telefone' => '(19) 99876-5432',
                'ativo' => true,
            ],
        );

        $clienteAbc = Cliente::query()->where('documento', '12.345.678/0001-90')->first();

        if ($clienteAbc) {
            $marcos->clientes()->sync([$clienteAbc->id]);
        }
    }
}
