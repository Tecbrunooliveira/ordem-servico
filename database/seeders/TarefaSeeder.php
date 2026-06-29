<?php

namespace Database\Seeders;

use App\Enums\TarefaCategoria;
use App\Enums\TarefaPrioridade;
use App\Enums\TarefaRecorrencia;
use App\Enums\TarefaStatus;
use App\Models\Tarefa;
use App\Models\TarefaComentario;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class TarefaSeeder extends Seeder
{
    public function run(): void
    {
        $joao = Usuario::query()->where('email', 'joao.silva@gestaotecnica.com.br')->first();
        $ana = Usuario::query()->where('email', 'ana.paula@gestaotecnica.com.br')->first();

        if (! $joao || ! $ana) {
            return;
        }

        $tarefa = Tarefa::query()->updateOrCreate(
            ['titulo' => 'Relatório mensal de atividades'],
            [
                'descricao' => 'Consolidar dados do mês anterior.',
                'status' => TarefaStatus::Pendente,
                'prioridade' => TarefaPrioridade::Alta,
                'data_vencimento' => now()->addDays(2)->toDateString(),
                'responsavel_id' => $joao->id,
                'categoria' => TarefaCategoria::Administrativa,
                'data_inicio' => now()->subDays(3)->toDateString(),
                'tempo_segundos' => 5400,
                'recorrencia' => TarefaRecorrencia::Mensal,
            ],
        );

        TarefaComentario::query()->firstOrCreate(
            [
                'tarefa_id' => $tarefa->id,
                'texto' => 'Revisei a planilha base. Falta consolidar o setor operacional.',
            ],
            [
                'usuario_id' => $ana->id,
                'autor' => $ana->nome,
            ],
        );

        Tarefa::query()->updateOrCreate(
            ['titulo' => 'Atualizar documentação NR-10'],
            [
                'descricao' => 'Revisar procedimentos de segurança.',
                'status' => TarefaStatus::EmAndamento,
                'prioridade' => TarefaPrioridade::Media,
                'data_vencimento' => now()->addDays(5)->toDateString(),
                'responsavel_id' => $ana->id,
                'categoria' => TarefaCategoria::Operacional,
                'data_inicio' => now()->subDays(2)->toDateString(),
                'tempo_segundos' => 3600,
                'recorrencia' => TarefaRecorrencia::Nenhuma,
            ],
        );
    }
}
