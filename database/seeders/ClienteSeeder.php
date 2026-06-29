<?php

namespace Database\Seeders;

use App\Models\Cliente;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        $clientes = [
            [
                'nome' => 'Cliente ABC Ltda',
                'documento' => '12.345.678/0001-90',
                'email' => 'contato@abc.com.br',
                'telefone' => '(11) 3456-7890',
                'cidade' => 'São Paulo',
                'estado' => 'SP',
                'rua' => 'Av. Paulista',
                'numero' => '1000',
                'bairro' => 'Bela Vista',
                'cep' => '01310-100',
                'endereco' => 'Av. Paulista, 1000 — Bela Vista',
                'ativo' => true,
            ],
            [
                'nome' => 'Indústria XYZ S.A.',
                'documento' => '98.765.432/0001-10',
                'email' => 'suporte@xyz.com.br',
                'telefone' => '(19) 3333-4444',
                'cidade' => 'Campinas',
                'estado' => 'SP',
                'rua' => 'Rod. Anhanguera',
                'numero' => 'km 95',
                'bairro' => 'Distrito Industrial',
                'cep' => '13065-900',
                'endereco' => 'Rod. Anhanguera, km 95 — Distrito Industrial',
                'ativo' => true,
            ],
        ];

        foreach ($clientes as $cliente) {
            Cliente::query()->updateOrCreate(
                ['documento' => $cliente['documento']],
                $cliente,
            );
        }
    }
}
