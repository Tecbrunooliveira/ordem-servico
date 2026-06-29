<?php

namespace Database\Seeders;

use App\Models\Empresa;
use Illuminate\Database\Seeder;

class EmpresaSeeder extends Seeder
{
    public function run(): void
    {
        Empresa::query()->firstOrCreate(
            ['id' => 1],
            [
                'nome_empresa' => 'Gestão Técnica',
                'razao_social' => 'Gestão Técnica Serviços Ltda',
                'cnpj' => '12.345.678/0001-90',
                'endereco' => 'Av. Paulista, 1000 — Bela Vista',
                'cidade' => 'São Paulo',
                'estado' => 'SP',
                'cep' => '01310-100',
                'telefone' => '(11) 3456-7890',
                'email' => 'contato@gestaotecnica.com.br',
                'site' => 'https://www.gestaotecnica.com.br',
            ],
        );
    }
}
