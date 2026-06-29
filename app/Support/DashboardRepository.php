<?php

namespace App\Support;

use App\Enums\OrdemServicoStatus;
use App\Enums\TarefaStatus;
use App\Models\OrdemServico;
use App\Models\Tarefa;
use Illuminate\Support\Carbon;

class DashboardRepository
{
    /** @return array<int, array<string, string>> */
    public static function stats(): array
    {
        $hoje = now()->toDateString();
        $statusAbertosTarefa = [TarefaStatus::Pendente->value, TarefaStatus::EmAndamento->value];
        $statusAbertosOs = [OrdemServicoStatus::Pendente->value, OrdemServicoStatus::EmAndamento->value];

        $tarefasHoje = Tarefa::query()
            ->where(function ($query) use ($hoje): void {
                $query->whereDate('data_vencimento', $hoje)
                    ->orWhereDate('data_inicio', $hoje);
            })
            ->whereIn('status', $statusAbertosTarefa)
            ->count();

        $tarefasAtraso = Tarefa::query()
            ->whereNotNull('data_vencimento')
            ->whereDate('data_vencimento', '<', $hoje)
            ->whereIn('status', $statusAbertosTarefa)
            ->count();

        $osAbertas = OrdemServico::query()
            ->whereIn('status', $statusAbertosOs)
            ->count();

        $osHoje = OrdemServico::query()
            ->whereDate('data_agendada', $hoje)
            ->whereIn('status', $statusAbertosOs)
            ->count();

        return [
            [
                'value' => (string) $tarefasHoje,
                'label' => 'Tarefas Hoje',
                'hint' => $tarefasAtraso === 1 ? '1 em atraso' : "{$tarefasAtraso} em atraso",
                'hintColor' => $tarefasAtraso > 0 ? 'text-red-500' : 'text-emerald-600',
                'icon' => 'clipboard-document-list',
                'iconBg' => 'bg-blue-100',
                'iconColor' => 'text-blue-600',
            ],
            [
                'value' => (string) $osAbertas,
                'label' => 'Ordem Serviço',
                'hint' => $osHoje === 1 ? '1 hoje' : "{$osHoje} hoje",
                'hintColor' => 'text-emerald-600',
                'icon' => 'document-text',
                'iconBg' => 'bg-emerald-100',
                'iconColor' => 'text-emerald-600',
            ],
        ];
    }

    /** @return array<int, array<string, string>> */
    public static function agendaHoje(): array
    {
        $hoje = now()->toDateString();

        return OrdemServico::query()
            ->with(['cliente', 'tecnico'])
            ->whereDate('data_agendada', $hoje)
            ->where('status', '!=', OrdemServicoStatus::Cancelada->value)
            ->orderBy('titulo')
            ->get()
            ->map(function (OrdemServico $ordem): array {
                $cliente = $ordem->cliente;
                $local = $cliente
                    ? trim(collect([$cliente->cidade, $cliente->estado])->filter()->implode(', '))
                    : '';

                return [
                    'time' => 'Hoje',
                    'title' => $ordem->titulo,
                    'description' => trim($ordem->tipo->label().($ordem->descricao ? ' — '.$ordem->descricao : '')),
                    'person' => $ordem->tecnico?->nome ?? ($ordem->participante ?: 'Sem responsável'),
                    'location' => $local !== '' ? $local : ($cliente?->nome ?? '—'),
                ];
            })
            ->all();
    }

    /** @return array<int, array<string, string>> */
    public static function notificacoes(): array
    {
        $hoje = now()->toDateString();
        $items = [];

        Tarefa::query()
            ->whereNotNull('data_vencimento')
            ->whereDate('data_vencimento', '<', $hoje)
            ->whereIn('status', [TarefaStatus::Pendente->value, TarefaStatus::EmAndamento->value])
            ->orderBy('data_vencimento')
            ->limit(3)
            ->get()
            ->each(function (Tarefa $tarefa) use (&$items): void {
                $vencimento = Carbon::parse($tarefa->data_vencimento)->startOfDay();
                $dias = (int) $vencimento->diffInDays(now()->startOfDay());

                $items[] = [
                    'type' => 'warning',
                    'title' => 'Tarefa em atraso',
                    'message' => sprintf(
                        'A tarefa "%s" está %d %s em atraso.',
                        $tarefa->titulo,
                        max($dias, 1),
                        max($dias, 1) === 1 ? 'dia' : 'dias',
                    ),
                    'time' => $vencimento->diffForHumans(),
                ];
            });

        $osHoje = OrdemServico::query()
            ->whereDate('data_agendada', $hoje)
            ->whereIn('status', [OrdemServicoStatus::Pendente->value, OrdemServicoStatus::EmAndamento->value])
            ->count();

        if ($osHoje > 0) {
            $items[] = [
                'type' => 'info',
                'title' => 'Ordens agendadas hoje',
                'message' => $osHoje === 1
                    ? 'Há 1 ordem de serviço agendada para hoje.'
                    : "Há {$osHoje} ordens de serviço agendadas para hoje.",
                'time' => 'hoje',
            ];
        }

        OrdemServico::query()
            ->where('status', OrdemServicoStatus::Concluida->value)
            ->whereNotNull('finalizada_em')
            ->orderByDesc('finalizada_em')
            ->limit(2)
            ->get()
            ->each(function (OrdemServico $ordem) use (&$items): void {
                $items[] = [
                    'type' => 'success',
                    'title' => 'Ordem concluída',
                    'message' => sprintf('A ordem "%s" foi finalizada.', $ordem->titulo),
                    'time' => $ordem->finalizada_em?->diffForHumans() ?? '—',
                ];
            });

        Tarefa::query()
            ->whereDate('data_vencimento', $hoje)
            ->whereIn('status', [TarefaStatus::Pendente->value, TarefaStatus::EmAndamento->value])
            ->orderBy('titulo')
            ->limit(2)
            ->get()
            ->each(function (Tarefa $tarefa) use (&$items): void {
                $items[] = [
                    'type' => 'info',
                    'title' => 'Tarefa vence hoje',
                    'message' => sprintf('"%s" tem vencimento previsto para hoje.', $tarefa->titulo),
                    'time' => 'hoje',
                ];
            });

        return array_slice($items, 0, 6);
    }
}
