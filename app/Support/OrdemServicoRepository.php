<?php

namespace App\Support;

use App\Enums\OrdemServicoStatus;
use App\Enums\OrdemServicoTipo;
use App\Models\OrdemServico;
use App\Models\OrdemServicoComentario;
use App\Models\OrdemServicoPausa;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;

class OrdemServicoRepository
{
    /** @return Builder<OrdemServico> */
    public static function query(): Builder
    {
        return OrdemServico::query()
            ->with(['comentarios', 'pausas', 'cliente', 'tecnico']);
    }

    /** @return array<int, array<string, mixed>> */
    public static function allAsArrays(): array
    {
        return self::query()
            ->orderByDesc('data_agendada')
            ->orderBy('hora_agendada')
            ->orderByDesc('id')
            ->get()
            ->map(fn (OrdemServico $ordem) => self::toArray($ordem))
            ->all();
    }

    /** @return array<string, mixed> */
    public static function findAsArray(int $id): array
    {
        $ordem = self::query()->findOrFail($id);

        return self::toArray($ordem);
    }

    /** @return array<int, array{id: int, nome: string, telefone: string}> */
    public static function tecnicosDisponiveis(): array
    {
        return Usuario::query()
            ->tecnicos()
            ->ativos()
            ->orderBy('nome')
            ->get(['id', 'nome', 'telefone'])
            ->map(fn (Usuario $usuario) => [
                'id' => $usuario->id,
                'nome' => $usuario->nome,
                'telefone' => $usuario->telefone ?? '',
            ])
            ->all();
    }

    /** @param  array<string, mixed>  $data */
    public static function createFromForm(array $data): int
    {
        $ordem = OrdemServico::query()->create([
            'cliente_id' => $data['cliente_id'],
            'tipo' => $data['tipo'],
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'] ?? null,
            'data_agendada' => $data['data_agendada'] ?? null,
            'hora_agendada' => ($data['data_agendada'] ?? null)
                ? self::normalizeHoraAgendada($data['hora_agendada'] ?? null)
                : null,
            'status' => OrdemServicoStatus::Pendente->value,
            'participante' => $data['participante'] ?? null,
            'participante_telefone' => $data['participante_telefone'] ?? null,
            'tecnico_id' => $data['tecnico_id'] ?? null,
        ]);

        return $ordem->id;
    }

    /** @param  array<string, mixed>  $data */
    public static function updateFromForm(int $id, array $data): void
    {
        OrdemServico::query()->whereKey($id)->update([
            'cliente_id' => $data['cliente_id'],
            'tipo' => $data['tipo'],
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'] ?? null,
            'data_agendada' => $data['data_agendada'] ?? null,
            'hora_agendada' => ($data['data_agendada'] ?? null)
                ? self::normalizeHoraAgendada($data['hora_agendada'] ?? null)
                : null,
            'status' => $data['status'],
            'participante' => $data['participante'] ?? null,
            'participante_telefone' => $data['participante_telefone'] ?? null,
            'tecnico_id' => $data['tecnico_id'] ?? null,
        ]);
    }

    /** @param  array<string, mixed>  $ordem */
    public static function persistFromArray(array $ordem): void
    {
        $participantes = self::normalizeParticipantes($ordem['participantes'] ?? []);

        OrdemServico::query()->whereKey($ordem['id'])->update([
            'cliente_id' => $ordem['cliente_id'],
            'tipo' => $ordem['tipo'],
            'titulo' => $ordem['titulo'],
            'descricao' => $ordem['descricao'] ?? null,
            'data_agendada' => $ordem['data_agendada'] ?: null,
            'hora_agendada' => ($ordem['data_agendada'] ?: null)
                ? self::normalizeHoraAgendada($ordem['hora_agendada'] ?? null)
                : null,
            'status' => $ordem['status'],
            'participante' => $ordem['participante'] ?? null,
            'participante_telefone' => $ordem['participante_telefone'] ?? null,
            'tecnico_id' => $ordem['tecnico_id'] ?? null,
            'tempo_segundos' => (int) ($ordem['tempo_segundos'] ?? 0),
            'pausada' => (bool) ($ordem['pausada'] ?? false),
            'descricao_servicos' => $ordem['descricao_servicos'] ?? null,
            'participante_1' => $participantes[0] ?: null,
            'participante_2' => $participantes[1] ?: null,
            'participante_3' => $participantes[2] ?: null,
            'participante_4' => $participantes[3] ?: null,
            'observacoes' => $ordem['observacoes'] ?? null,
            'iniciada_em' => $ordem['iniciada_em'] ?? null,
            'finalizada_em' => $ordem['finalizada_em'] ?? null,
        ]);
    }

    public static function delete(int $id): void
    {
        OrdemServico::query()->whereKey($id)->delete();
    }

    public static function addComentario(int $ordemId, string $texto): OrdemServicoComentario
    {
        $usuario = auth()->user();

        return OrdemServicoComentario::query()->create([
            'ordem_servico_id' => $ordemId,
            'usuario_id' => $usuario?->id,
            'autor' => $usuario?->nome ?? 'Usuário',
            'texto' => $texto,
        ]);
    }

    public static function addPausa(int $ordemId, string $motivo): OrdemServicoPausa
    {
        return OrdemServicoPausa::query()->create([
            'ordem_servico_id' => $ordemId,
            'motivo' => $motivo,
            'pausada_em' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    public static function toArray(OrdemServico $ordem): array
    {
        $participantes = array_pad($ordem->participantesLista(), 4, '');

        return [
            'id' => $ordem->id,
            'cliente_id' => $ordem->cliente_id,
            'tipo' => $ordem->tipo->value,
            'titulo' => $ordem->titulo,
            'descricao' => $ordem->descricao ?? '',
            'data_agendada' => $ordem->data_agendada?->toDateString(),
            'hora_agendada' => self::formatHoraParaInput($ordem->hora_agendada),
            'status' => $ordem->status->value,
            'participante' => $ordem->participante ?? '',
            'participante_telefone' => $ordem->participante_telefone ?? '',
            'tecnico_id' => $ordem->tecnico_id,
            'tempo_segundos' => (int) $ordem->tempo_segundos,
            'pausada' => (bool) $ordem->pausada,
            'descricao_servicos' => $ordem->descricao_servicos ?? '',
            'participantes' => $participantes,
            'observacoes' => $ordem->observacoes ?? '',
            'iniciada_em' => $ordem->iniciada_em?->toDateTimeString(),
            'finalizada_em' => $ordem->finalizada_em?->toDateTimeString(),
            'comentarios' => $ordem->comentarios
                ->sortBy(OrdemServicoComentario::CREATED_AT)
                ->values()
                ->map(fn (OrdemServicoComentario $comentario) => [
                    'id' => $comentario->id,
                    'autor' => $comentario->autor,
                    'texto' => $comentario->texto,
                    'criado_em' => $comentario->criado_em->toDateTimeString(),
                ])
                ->all(),
            'pausas' => $ordem->pausas
                ->sortBy('pausada_em')
                ->values()
                ->map(fn (OrdemServicoPausa $pausa) => [
                    'motivo' => $pausa->motivo,
                    'em' => $pausa->pausada_em->toDateTimeString(),
                ])
                ->all(),
        ];
    }

    /** @return array<int, string> */
    private static function normalizeParticipantes(mixed $participantes): array
    {
        if (is_array($participantes)) {
            return array_pad(array_map(fn ($nome) => trim((string) $nome), array_slice($participantes, 0, 4)), 4, '');
        }

        return ['', '', '', ''];
    }

    public static function normalizeHoraAgendada(?string $hora): ?string
    {
        $hora = trim((string) $hora);

        if ($hora === '') {
            return null;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $hora)) {
            return $hora.':00';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $hora)) {
            return $hora;
        }

        return null;
    }

    public static function formatHoraParaInput(mixed $hora): ?string
    {
        if ($hora === null || $hora === '') {
            return null;
        }

        $valor = (string) $hora;

        return strlen($valor) >= 5 ? substr($valor, 0, 5) : $valor;
    }

    public static function formatHoraLabel(?string $hora): string
    {
        $hora = self::formatHoraParaInput($hora);

        return $hora ?? 'Dia todo';
    }

    public static function formatAgendamento(?string $data, ?string $hora = null): string
    {
        if (blank($data)) {
            return '—';
        }

        $texto = \Illuminate\Support\Carbon::parse($data)->format('d/m/Y');
        $horaFormatada = self::formatHoraParaInput($hora);

        if ($horaFormatada) {
            $texto .= ' · '.$horaFormatada;
        }

        return $texto;
    }

    public static function agendamentoDatetime(?string $data, ?string $hora): ?string
    {
        if (blank($data)) {
            return null;
        }

        $horaFormatada = self::formatHoraParaInput($hora);

        return $horaFormatada ? $data.'T'.$horaFormatada : $data;
    }
}
