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
            ->orderBy('hora_agendada')
            ->orderBy('titulo')
            ->get()
            ->map(function (OrdemServico $ordem): array {
                $cliente = $ordem->cliente;
                $local = $cliente
                    ? trim(collect([$cliente->cidade, $cliente->estado])->filter()->implode(', '))
                    : '';

                return [
                    'time' => OrdemServicoRepository::formatHoraLabel($ordem->hora_agendada),
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
        return NotificationCenter::forDashboard();
    }
}
