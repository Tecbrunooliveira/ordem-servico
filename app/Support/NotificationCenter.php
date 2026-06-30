<?php

namespace App\Support;

use App\Enums\OrdemServicoStatus;
use App\Enums\TarefaStatus;
use App\Models\OrdemServico;
use App\Models\Tarefa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class NotificationCenter
{
    private const CACHE_PREFIX = 'notificacoes_lidas.';

    /** @return array<int, array<string, mixed>> */
    public static function all(?int $userId = null): array
    {
        $userId ??= auth()->id();

        if (! $userId) {
            return [];
        }

        $readIds = self::readIds($userId);
        $items = self::buildItems();

        return array_map(function (array $item) use ($readIds): array {
            $item['read'] = in_array($item['id'], $readIds, true);

            return $item;
        }, $items);
    }

    /** @return array<int, array<string, string>> */
    public static function forDashboard(?int $userId = null): array
    {
        return array_map(
            fn (array $item): array => [
                'type' => $item['type'],
                'title' => $item['title'],
                'message' => $item['message'],
                'time' => $item['time'],
            ],
            self::all($userId),
        );
    }

    public static function unreadCount(?int $userId = null): int
    {
        $userId ??= auth()->id();

        if (! $userId) {
            return 0;
        }

        return count(array_filter(self::all($userId), fn (array $item): bool => ! $item['read']));
    }

    public static function tarefasAlertaCount(): int
    {
        $hoje = now()->toDateString();
        $statusAbertos = [TarefaStatus::Pendente->value, TarefaStatus::EmAndamento->value];

        return self::tarefaQuery()
            ->whereIn('status', $statusAbertos)
            ->where(function ($query) use ($hoje): void {
                $query->whereDate('data_vencimento', '<', $hoje)
                    ->orWhereDate('data_vencimento', $hoje);
            })
            ->count();
    }

    /** @return Builder<Tarefa> */
    private static function tarefaQuery(): Builder
    {
        return ClienteAccess::aplicarFiltroCliente(Tarefa::query());
    }

    /** @return Builder<OrdemServico> */
    private static function ordemServicoQuery(): Builder
    {
        return ClienteAccess::aplicarFiltroCliente(OrdemServico::query());
    }

    public static function markAsRead(int $userId, string $id): void
    {
        $ids = self::readIds($userId);

        if (! in_array($id, $ids, true)) {
            $ids[] = $id;
            Cache::forever(self::cacheKey($userId), $ids);
        }
    }

    public static function markAllAsRead(int $userId): void
    {
        $ids = array_column(self::buildItems(), 'id');
        Cache::forever(self::cacheKey($userId), array_values(array_unique($ids)));
    }

    /** @return array<int, string> */
    public static function readIds(int $userId): array
    {
        return Cache::get(self::cacheKey($userId), []);
    }

    /** @return array<int, array<string, mixed>> */
    private static function buildItems(): array
    {
        $hoje = now()->toDateString();
        $items = [];
        $statusAbertosTarefa = [TarefaStatus::Pendente->value, TarefaStatus::EmAndamento->value];

        self::tarefaQuery()
            ->whereNotNull('data_vencimento')
            ->whereDate('data_vencimento', '<', $hoje)
            ->whereIn('status', $statusAbertosTarefa)
            ->orderBy('data_vencimento')
            ->limit(5)
            ->get()
            ->each(function (Tarefa $tarefa) use (&$items): void {
                $vencimento = Carbon::parse($tarefa->data_vencimento)->startOfDay();
                $dias = max((int) $vencimento->diffInDays(now()->startOfDay()), 1);

                $items[] = [
                    'id' => 'tarefa-atraso-'.$tarefa->id,
                    'type' => 'warning',
                    'title' => 'Tarefa em atraso',
                    'message' => sprintf(
                        'A tarefa "%s" está %d %s em atraso.',
                        $tarefa->titulo,
                        $dias,
                        $dias === 1 ? 'dia' : 'dias',
                    ),
                    'time' => $vencimento->diffForHumans(),
                    'url' => route('tarefas.index'),
                ];
            });

        $osHoje = self::ordemServicoQuery()
            ->whereDate('data_agendada', $hoje)
            ->whereIn('status', [OrdemServicoStatus::Pendente->value, OrdemServicoStatus::EmAndamento->value])
            ->count();

        if ($osHoje > 0) {
            $items[] = [
                'id' => 'os-hoje-'.$hoje,
                'type' => 'info',
                'title' => 'Ordens agendadas hoje',
                'message' => $osHoje === 1
                    ? 'Há 1 ordem de serviço agendada para hoje.'
                    : "Há {$osHoje} ordens de serviço agendadas para hoje.",
                'time' => 'hoje',
                'url' => route('agenda.index'),
            ];
        }

        self::tarefaQuery()
            ->whereDate('data_vencimento', $hoje)
            ->whereIn('status', $statusAbertosTarefa)
            ->orderBy('titulo')
            ->limit(5)
            ->get()
            ->each(function (Tarefa $tarefa) use (&$items): void {
                $items[] = [
                    'id' => 'tarefa-hoje-'.$tarefa->id,
                    'type' => 'info',
                    'title' => 'Tarefa vence hoje',
                    'message' => sprintf('"%s" tem vencimento previsto para hoje.', $tarefa->titulo),
                    'time' => 'hoje',
                    'url' => route('tarefas.index'),
                ];
            });

        self::ordemServicoQuery()
            ->where('status', OrdemServicoStatus::Concluida->value)
            ->whereNotNull('finalizada_em')
            ->where('finalizada_em', '>=', now()->subDay())
            ->orderByDesc('finalizada_em')
            ->limit(3)
            ->get()
            ->each(function (OrdemServico $ordem) use (&$items): void {
                $items[] = [
                    'id' => 'os-concluida-'.$ordem->id,
                    'type' => 'success',
                    'title' => 'Ordem concluída',
                    'message' => sprintf('A ordem "%s" foi finalizada.', $ordem->titulo),
                    'time' => $ordem->finalizada_em?->diffForHumans() ?? '—',
                    'url' => route('ordens-servico.index'),
                ];
            });

        return array_slice($items, 0, 12);
    }

    private static function cacheKey(int $userId): string
    {
        return self::CACHE_PREFIX.$userId;
    }
}
