<?php

namespace Database\Seeders;

use App\Enums\OrdemServicoStatus;
use App\Enums\OrdemServicoTipo;
use App\Models\Cliente;
use App\Models\OrdemServico;
use Illuminate\Database\Seeder;

class OrdemServicoSeeder extends Seeder
{
    public function run(): void
    {
        $clientes = Cliente::query()->get();

        if ($clientes->isEmpty()) {
            return;
        }

        $ordens = [
            [
                'cliente_id' => $clientes[0]->id,
                'tipo' => OrdemServicoTipo::VisitaTecnica,
                'titulo' => 'Visita Técnica - Manutenção preventiva',
                'descricao' => 'Inspeção de equipamentos e relatório técnico.',
                'data_agendada' => now()->toDateString(),
                'status' => OrdemServicoStatus::EmAndamento,
            ],
            [
                'cliente_id' => $clientes[0]->id,
                'tipo' => OrdemServicoTipo::Treinamento,
                'titulo' => 'Treinamento NR-10',
                'descricao' => 'Capacitação em segurança elétrica.',
                'data_agendada' => now()->addDays(2)->toDateString(),
                'status' => OrdemServicoStatus::Pendente,
            ],
            [
                'cliente_id' => $clientes->count() > 1 ? $clientes[1]->id : $clientes[0]->id,
                'tipo' => OrdemServicoTipo::Manutencao,
                'titulo' => 'Manutenção corretiva - Linha 3',
                'descricao' => 'Substituição de componentes da linha de produção.',
                'data_agendada' => now()->addDays(5)->toDateString(),
                'status' => OrdemServicoStatus::Pendente,
            ],
            [
                'cliente_id' => $clientes->count() > 1 ? $clientes[1]->id : $clientes[0]->id,
                'tipo' => OrdemServicoTipo::VisitaTecnica,
                'titulo' => 'Visita técnica - Obra Delta',
                'descricao' => 'Verificação de instalações elétricas.',
                'data_agendada' => now()->addDays(8)->toDateString(),
                'status' => OrdemServicoStatus::Pendente,
            ],
        ];

        foreach ($ordens as $ordem) {
            OrdemServico::query()->firstOrCreate(
                ['titulo' => $ordem['titulo'], 'cliente_id' => $ordem['cliente_id']],
                $ordem,
            );
        }
    }
}
