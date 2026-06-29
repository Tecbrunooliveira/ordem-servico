<?php

namespace App\Support;

use App\Models\Tarefa;
use App\Models\TarefaAnexo;
use App\Models\TarefaComentario;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class TarefaRepository
{
    /** @return Builder<Tarefa> */
    public static function query(): Builder
    {
        return Tarefa::query()->with(['responsavel', 'comentarios', 'anexos']);
    }

    /** @return array<int, array<string, mixed>> */
    public static function allAsArrays(): array
    {
        return self::query()
            ->orderByDesc('data_vencimento')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Tarefa $tarefa) => self::toArray($tarefa))
            ->all();
    }

    /** @return array<string, mixed> */
    public static function findAsArray(int $id): array
    {
        return self::toArray(self::query()->findOrFail($id));
    }

    /** @return array<int, string> */
    public static function responsaveisDisponiveis(): array
    {
        return Usuario::query()
            ->tecnicos()
            ->ativos()
            ->orderBy('nome')
            ->pluck('nome')
            ->all();
    }

    public static function resolveResponsavelId(string $nome): ?int
    {
        if ($nome === '') {
            return null;
        }

        return Usuario::query()
            ->tecnicos()
            ->where('nome', $nome)
            ->value('id');
    }

    /** @param  array<string, mixed>  $data */
    public static function createFromForm(array $data): int
    {
        $tarefa = Tarefa::query()->create([
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'] ?? null,
            'status' => $data['status'],
            'prioridade' => $data['prioridade'],
            'data_vencimento' => $data['data_vencimento'] ?? null,
            'responsavel_id' => self::resolveResponsavelId($data['responsavel'] ?? ''),
            'categoria' => $data['categoria'],
            'data_inicio' => $data['data_inicio'],
            'tempo_segundos' => (int) ($data['tempo_segundos'] ?? 0),
            'recorrencia' => $data['recorrencia'],
        ]);

        return $tarefa->id;
    }

    /** @param  array<string, mixed>  $data */
    public static function updateFromForm(int $id, array $data): void
    {
        Tarefa::query()->whereKey($id)->update([
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'] ?? null,
            'status' => $data['status'],
            'prioridade' => $data['prioridade'],
            'data_vencimento' => $data['data_vencimento'] ?? null,
            'responsavel_id' => self::resolveResponsavelId($data['responsavel'] ?? ''),
            'categoria' => $data['categoria'],
            'data_inicio' => $data['data_inicio'],
            'tempo_segundos' => (int) ($data['tempo_segundos'] ?? 0),
            'recorrencia' => $data['recorrencia'],
        ]);
    }

    /** @param  array<string, mixed>  $tarefa */
    public static function persistFromArray(array $tarefa): void
    {
        Tarefa::query()->whereKey($tarefa['id'])->update([
            'titulo' => $tarefa['titulo'],
            'descricao' => $tarefa['descricao'] ?? null,
            'status' => $tarefa['status'],
            'prioridade' => $tarefa['prioridade'],
            'data_vencimento' => $tarefa['data_vencimento'] ?: null,
            'responsavel_id' => self::resolveResponsavelId($tarefa['responsavel'] ?? ''),
            'categoria' => $tarefa['categoria'],
            'data_inicio' => $tarefa['data_inicio'],
            'tempo_segundos' => (int) ($tarefa['tempo_segundos'] ?? 0),
            'recorrencia' => $tarefa['recorrencia'],
        ]);
    }

    public static function delete(int $id): void
    {
        $tarefa = Tarefa::query()->with('anexos')->find($id);

        if (! $tarefa) {
            return;
        }

        foreach ($tarefa->anexos as $anexo) {
            Storage::disk('public')->delete($anexo->caminho);
        }

        $tarefa->delete();
    }

    public static function addComentario(int $tarefaId, string $texto): TarefaComentario
    {
        $usuario = auth()->user();

        return TarefaComentario::query()->create([
            'tarefa_id' => $tarefaId,
            'usuario_id' => $usuario?->id,
            'autor' => $usuario?->nome ?? 'Usuário',
            'texto' => $texto,
        ]);
    }

    public static function storeAnexo(int $tarefaId, TemporaryUploadedFile $arquivo): TarefaAnexo
    {
        $caminho = $arquivo->store('tarefas/'.$tarefaId, 'public');

        return TarefaAnexo::query()->create([
            'tarefa_id' => $tarefaId,
            'nome_arquivo' => $arquivo->getClientOriginalName(),
            'caminho' => $caminho,
            'tamanho_bytes' => $arquivo->getSize(),
            'tipo_mime' => $arquivo->getMimeType(),
        ]);
    }

    public static function deleteAnexo(int $anexoId): void
    {
        $anexo = TarefaAnexo::query()->find($anexoId);

        if (! $anexo) {
            return;
        }

        Storage::disk('public')->delete($anexo->caminho);
        $anexo->delete();
    }

    /** @return array<string, mixed> */
    public static function toArray(Tarefa $tarefa): array
    {
        return [
            'id' => $tarefa->id,
            'titulo' => $tarefa->titulo,
            'descricao' => $tarefa->descricao ?? '',
            'status' => $tarefa->status->value,
            'prioridade' => $tarefa->prioridade->value,
            'data_vencimento' => $tarefa->data_vencimento?->toDateString(),
            'responsavel' => $tarefa->responsavel?->nome ?? '—',
            'categoria' => $tarefa->categoria->value,
            'data_inicio' => $tarefa->data_inicio?->toDateString(),
            'tempo_segundos' => (int) $tarefa->tempo_segundos,
            'recorrencia' => $tarefa->recorrencia->value,
            'anexos' => $tarefa->anexos->map(fn (TarefaAnexo $anexo) => [
                'id' => $anexo->id,
                'nome' => $anexo->nome_arquivo,
                'tamanho' => $anexo->tamanho_formatado,
            ])->all(),
            'comentarios' => $tarefa->comentarios
                ->sortBy(TarefaComentario::CREATED_AT)
                ->values()
                ->map(fn (TarefaComentario $comentario) => [
                    'id' => $comentario->id,
                    'autor' => $comentario->autor,
                    'texto' => $comentario->texto,
                    'criado_em' => $comentario->criado_em->toDateTimeString(),
                ])
                ->all(),
        ];
    }
}
