<?php

namespace App\Support;

use App\Enums\OrdemServicoStatus;
use App\Enums\OrdemServicoTipo;
use App\Models\OrdemServico;

class AgendaRepository
{
    /** @return array<int, array<string, mixed>> */
    public static function ordensParaAgenda(): array
    {
        return OrdemServico::query()
            ->with('cliente:id,nome')
            ->whereNotNull('data_agendada')
            ->orderBy('data_agendada')
            ->orderBy('hora_agendada')
            ->get()
            ->map(function (OrdemServico $ordem) {
                $tipo = $ordem->tipo instanceof OrdemServicoTipo
                    ? $ordem->tipo
                    : OrdemServicoTipo::from($ordem->tipo);

                $status = $ordem->status instanceof OrdemServicoStatus
                    ? $ordem->status
                    : OrdemServicoStatus::from($ordem->status);

                return [
                    'id' => $ordem->id,
                    'titulo' => $ordem->titulo,
                    'cliente' => $ordem->cliente?->nome ?? '—',
                    'tipo' => $tipo->value,
                    'tipoLabel' => $tipo->label(),
                    'tipoColor' => $tipo->color(),
                    'status' => $status->label(),
                    'descricao' => $ordem->descricao ?? '',
                    'data_agendada' => $ordem->data_agendada?->toDateString(),
                    'hora_agendada' => OrdemServicoRepository::formatHoraParaInput($ordem->hora_agendada),
                ];
            })
            ->all();
    }

    public static function reagendar(int $id, string $date): void
    {
        OrdemServico::query()->whereKey($id)->update([
            'data_agendada' => $date,
        ]);
    }

    /** @return array<string, mixed>|null */
    public static function eventoDetalhe(int $id): ?array
    {
        $ordem = collect(self::ordensParaAgenda())->firstWhere('id', $id);

        if (! $ordem) {
            return null;
        }

        return [
            'id' => $ordem['id'],
            'titulo' => $ordem['titulo'],
            'cliente' => $ordem['cliente'],
            'tipo' => $ordem['tipoLabel'],
            'tipoColor' => $ordem['tipoColor'],
            'status' => $ordem['status'],
            'descricao' => $ordem['descricao'],
            'data' => OrdemServicoRepository::formatAgendamento(
                $ordem['data_agendada'],
                $ordem['hora_agendada'] ?? null,
            ),
        ];
    }
}
