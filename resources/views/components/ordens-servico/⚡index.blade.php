<?php

use App\Enums\OrdemServicoStatus;
use App\Enums\OrdemServicoTipo;
use App\Support\ClienteStore;
use App\Support\EmpresaConfig;
use App\Support\OrdemServicoRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;
use WireUi\Traits\WireUiActions;

new class extends Component
{
    use WireUiActions;

    public bool $showForm = false;

    public ?int $editingId = null;

    public int $nextId = 6;

    /** @var array<int, array<string, mixed>> */
    public array $ordens = [];

    /** @var array<int, array<string, mixed>> */
    public array $clientes = [];

    public ?int $cliente_id = null;

    public string $tipo = '';

    public string $titulo = '';

    public string $descricao = '';

    public string $data_agendada = '';

    public string $hora_agendada = '';

    public string $participante = '';

    public string $participante_telefone = '';

    public ?int $tecnico_id = null;

    public string $status = 'pendente';

    public string $busca = '';

    public string $filtroStatus = '';

    public string $filtroTipo = '';

    public string $filtroCliente = '';

    public string $filtroAgendamento = '';

    public string $buscaClienteForm = '';

    public string $buscaClienteFiltro = '';

    public bool $showVisualizar = false;

    /** @var array<string, mixed>|null */
    public ?array $visualizarOrdem = null;

    public string $novoComentario = '';

    public int $nextComentarioId = 10;

    public ?int $runningOrdemId = null;

    public ?int $runningOrdemStartedAt = null;

    public string $execDescricaoServicos = '';

    public string $execParticipante1 = '';

    public string $execParticipante2 = '';

    public string $execParticipante3 = '';

    public string $execParticipante4 = '';

    public string $execObservacoes = '';

    public ?int $pausandoOrdemId = null;

    public string $motivoPausa = '';

    public ?int $finalizandoOrdemId = null;

    public string $execTempoTotal = '00:00:00';

    public bool $finalizacaoRetomarTimer = false;

    /** @var array<int, array{id: int, nome: string}> */
    public array $tecnicosDisponiveis = [];

    public function mount(): void
    {
        $this->carregarTecnicos();
        $this->carregarClientes();
        $this->carregarOrdens();

        $data = request()->query('data');

        if ($data && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            $this->data_agendada = $data;
            $this->showForm = true;
        }
    }

    private function carregarTecnicos(): void
    {
        $this->tecnicosDisponiveis = OrdemServicoRepository::tecnicosDisponiveis();
    }

    private function carregarClientes(): void
    {
        $this->clientes = collect(ClienteStore::all())
            ->filter(fn (array $cliente) => $cliente['ativo'] ?? true)
            ->map(fn (array $cliente) => [
                'id' => $cliente['id'],
                'nome' => $cliente['nome'],
                'documento' => $cliente['documento'] ?? '',
                'ativo' => $cliente['ativo'] ?? true,
            ])
            ->values()
            ->all();
    }

    private function carregarOrdens(): void
    {
        $this->ordens = OrdemServicoRepository::allAsArrays();
    }

    private function persistOrdemIndex(int $index): void
    {
        OrdemServicoRepository::persistFromArray($this->ordens[$index]);
        $this->ordens[$index] = OrdemServicoRepository::findAsArray($this->ordens[$index]['id']);
    }

    protected function rules(): array
    {
        $clienteIds = collect($this->clientesAtivos())
            ->pluck('id')
            ->implode(',');

        return [
            'cliente_id' => ['required', 'integer', 'in:'.$clienteIds],
            'tipo' => ['required', 'in:'.implode(',', array_column(OrdemServicoTipo::cases(), 'value'))],
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:10000'],
            'data_agendada' => ['nullable', 'date'],
            'hora_agendada' => ['nullable', 'date_format:H:i'],
            'participante' => ['nullable', 'string', 'max:255'],
            'participante_telefone' => ['nullable', 'string', 'max:20'],
            'tecnico_id' => ['nullable', 'integer', Rule::in(collect($this->tecnicosDisponiveis)->pluck('id'))],
            'status' => ['required', 'in:'.implode(',', array_column(OrdemServicoStatus::cases(), 'value'))],
        ];
    }

    private function makeOrdem(
        int $id,
        int $clienteId,
        OrdemServicoTipo $tipo,
        string $titulo,
        string $descricao,
        ?string $dataAgendada,
        OrdemServicoStatus $status,
        array $comentarios = [],
        int $tempoSegundos = 0,
        bool $pausada = false,
        string $descricaoServicos = '',
        array $participantes = [],
        string $observacoes = '',
        ?string $iniciadaEm = null,
        ?string $finalizadaEm = null,
        string $participante = '',
        string $participanteTelefone = '',
        ?int $tecnicoId = null,
        array $pausas = [],
    ): array {
        return $this->normalizeOrdem([
            'id' => $id,
            'cliente_id' => $clienteId,
            'tipo' => $tipo->value,
            'titulo' => $titulo,
            'descricao' => $descricao,
            'data_agendada' => $dataAgendada,
            'status' => $status->value,
            'comentarios' => $comentarios,
            'tempo_segundos' => $tempoSegundos,
            'pausada' => $pausada,
            'descricao_servicos' => $descricaoServicos,
            'participantes' => $participantes,
            'observacoes' => $observacoes,
            'iniciada_em' => $iniciadaEm,
            'finalizada_em' => $finalizadaEm,
            'participante' => $participante,
            'participante_telefone' => $participanteTelefone,
            'tecnico_id' => $tecnicoId,
            'pausas' => $pausas,
        ]);
    }

    /** @param  array<string, mixed>  $ordem */
    private function normalizeOrdem(array $ordem): array
    {
        $normalizada = array_merge([
            'tempo_segundos' => 0,
            'pausada' => false,
            'descricao_servicos' => '',
            'participantes' => ['', '', '', ''],
            'observacoes' => '',
            'participante' => '',
            'participante_telefone' => '',
            'tecnico_id' => null,
            'pausas' => [],
            'iniciada_em' => null,
            'finalizada_em' => null,
            'comentarios' => [],
        ], $ordem);

        $normalizada['participantes'] = $this->participantesLista($normalizada['participantes'] ?? []);

        return $normalizada;
    }

    /** @return array<int, string> */
    public function participantesLista(mixed $participantes): array
    {
        if (is_array($participantes)) {
            return array_pad(array_map(fn ($nome) => trim((string) $nome), array_slice($participantes, 0, 4)), 4, '');
        }

        if (is_string($participantes) && trim($participantes) !== '') {
            $partes = array_map('trim', explode(',', $participantes));

            return array_pad(array_slice($partes, 0, 4), 4, '');
        }

        return ['', '', '', ''];
    }

    /** @param  array<int, string>|mixed  $participantes */
    private function carregarParticipantesExecucao(mixed $participantes): void
    {
        [$this->execParticipante1, $this->execParticipante2, $this->execParticipante3, $this->execParticipante4] = $this->participantesLista($participantes);
    }

    /** @return array<int, string> */
    private function montarParticipantesExecucao(): array
    {
        return [
            trim($this->execParticipante1),
            trim($this->execParticipante2),
            trim($this->execParticipante3),
            trim($this->execParticipante4),
        ];
    }

    /** @return array<int, string> */
    public function participantesPreenchidos(mixed $participantes): array
    {
        return collect($this->participantesLista($participantes))
            ->filter(fn (string $nome) => $nome !== '')
            ->values()
            ->all();
    }

    public function colunasParticipantes(int $quantidade): int
    {
        return $quantidade === 1 ? 1 : 2;
    }

    /** @return array{id: int, autor: string, texto: string, criado_em: string} */
    private function makeComentario(int $id, string $autor, string $texto, Carbon $createdAt): array
    {
        return [
            'id' => $id,
            'autor' => $autor,
            'texto' => $texto,
            'criado_em' => $createdAt->toDateTimeString(),
        ];
    }

    /** @return array<string, mixed> */
    private function findOrdem(int $id): array
    {
        foreach ($this->ordens as $ordem) {
            if ($ordem['id'] === $id) {
                return $this->normalizeOrdem($ordem);
            }
        }

        throw new \RuntimeException('Ordem de serviço não encontrada.');
    }

    private function findOrdemIndex(int $id): int
    {
        foreach ($this->ordens as $index => $ordem) {
            if ($ordem['id'] === $id) {
                return $index;
            }
        }

        throw new \RuntimeException('Ordem de serviço não encontrada.');
    }

    /** @return array<string, mixed>|null */
    private function findCliente(int $id): ?array
    {
        $cliente = ClienteStore::find($id);

        if ($cliente) {
            return $cliente;
        }

        foreach ($this->clientes as $cliente) {
            if ($cliente['id'] === $id) {
                return $cliente;
            }
        }

        return null;
    }

    /** @return array<int, array<string, mixed>> */
    private function clientesAtivos(): array
    {
        return collect($this->clientes)
            ->filter(fn (array $cliente) => $cliente['ativo'])
            ->sortBy('nome')
            ->values()
            ->all();
    }

    public function nomeCliente(int $clienteId): string
    {
        return $this->findCliente($clienteId)['nome'] ?? '—';
    }

    public function documentoCliente(int $clienteId): string
    {
        return $this->findCliente($clienteId)['documento'] ?? '';
    }

    public function nomeTecnico(?int $tecnicoId): string
    {
        if (! $tecnicoId) {
            return '—';
        }

        return collect($this->tecnicosDisponiveis)->firstWhere('id', $tecnicoId)['nome'] ?? '—';
    }

    private function tituloChamadoPdf(string $titulo, string $tipo): string
    {
        $titulo = trim($titulo);

        if ($titulo === '') {
            return '';
        }

        $tituloLower = mb_strtolower($titulo);
        $tipoLower = mb_strtolower($tipo);

        if ($tituloLower === $tipoLower) {
            return '';
        }

        foreach ([' ', ' - ', ' – ', ' — ', ': '] as $separador) {
            $prefixo = $tipoLower.$separador;

            if (str_starts_with($tituloLower, $prefixo)) {
                $restante = trim(mb_substr($titulo, mb_strlen($tipo.$separador)));

                return $restante !== '' ? $restante : '';
            }
        }

        return $titulo;
    }

    /** @return array<string, mixed> */
    private function dadosPdfOrdem(int $id): array
    {
        $ordem = $this->findOrdem($id);
        $cliente = $this->findCliente($ordem['cliente_id']) ?? [
            'nome' => '—',
            'documento' => '',
            'email' => '',
            'telefone' => '',
        ];
        $tipo = OrdemServicoTipo::from($ordem['tipo'])->label();
        $tituloChamado = $this->tituloChamadoPdf($ordem['titulo'] ?? '', $tipo);

        $dataDocumento = $ordem['finalizada_em']
            ? Carbon::parse($ordem['finalizada_em'])->format('d/m/Y')
            : ($ordem['data_agendada']
                ? OrdemServicoRepository::formatAgendamento($ordem['data_agendada'], $ordem['hora_agendada'] ?? null)
                : now()->format('d/m/Y'));

        $participantes = $this->participantesPreenchidos($ordem['participantes'] ?? []);

        return [
            'empresa' => EmpresaConfig::get(),
            'ordem' => $ordem,
            'cliente' => $cliente,
            'clienteEndereco' => ClienteStore::enderecoCompleto($cliente),
            'tipo' => $tipo,
            'tituloChamado' => $tituloChamado,
            'tecnico' => $this->nomeTecnico($ordem['tecnico_id'] ?? null),
            'numeroOrdem' => str_pad((string) $ordem['id'], 3, '0', STR_PAD_LEFT),
            'dataDocumento' => $dataDocumento,
            'participantes' => $participantes,
            'tempoOrdem' => $this->formatarTempo($this->segundosOrdemAtual($ordem['tempo_segundos'] ?? 0, $ordem['id'])),
        ];
    }

    public function gerarPdf(int $id): ?StreamedResponse
    {
        try {
            $dados = $this->dadosPdfOrdem($id);
        } catch (\RuntimeException) {
            $this->notification()->send([
                'icon' => 'error',
                'title' => 'Ordem não encontrada',
                'description' => 'Não foi possível gerar o PDF desta ordem.',
                'timeout' => 3000,
            ]);

            return null;
        }

        $pdf = Pdf::loadView('pdf.ordem-servico', $dados)
            ->setPaper('a4')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        $arquivo = sprintf('OS-%s.pdf', str_pad((string) $id, 3, '0', STR_PAD_LEFT));

        return response()->streamDownload(
            fn () => print($pdf->output()),
            $arquivo,
            ['Content-Type' => 'application/pdf'],
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function filtrarClientesPorBusca(string $busca): array
    {
        $busca = mb_strtolower(trim($busca));

        if ($busca === '') {
            return [];
        }

        return collect($this->clientesAtivos())
            ->filter(function (array $cliente) use ($busca): bool {
                if (str_contains(mb_strtolower($cliente['nome']), $busca)) {
                    return true;
                }

                $termoDocumento = preg_replace('/\D/', '', $busca);

                if ($termoDocumento === '') {
                    return false;
                }

                $documento = preg_replace('/\D/', '', $cliente['documento'] ?? '');

                return str_contains($documento, $termoDocumento);
            })
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function clientesFormulario(): array
    {
        return $this->filtrarClientesPorBusca($this->buscaClienteForm);
    }

    /** @return array<int, array<string, mixed>> */
    public function clientesFiltroBusca(): array
    {
        return $this->filtrarClientesPorBusca($this->buscaClienteFiltro);
    }

    public function selecionarClienteFiltro(int $id): void
    {
        if (! collect($this->clientesAtivos())->contains(fn (array $cliente) => $cliente['id'] === $id)) {
            return;
        }

        $this->filtroCliente = (string) $id;
        $this->buscaClienteFiltro = '';
    }

    public function limparClienteFiltro(): void
    {
        $this->filtroCliente = '';
        $this->buscaClienteFiltro = '';
    }

    public function selecionarCliente(int $id): void
    {
        $cliente = collect($this->clientesAtivos())->firstWhere('id', $id);

        if (! $cliente) {
            return;
        }

        $this->cliente_id = $id;
        $this->buscaClienteForm = '';
        $this->resetValidation('cliente_id');
    }

    public function alterarCliente(): void
    {
        $this->cliente_id = null;
        $this->buscaClienteForm = '';
    }

    public function descricaoResumo(?string $html, int $limit = 100): string
    {
        $texto = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', $html ?? '')));
        $texto = preg_replace('/\s+/', ' ', $texto) ?? '';

        if ($texto === '') {
            return '';
        }

        return mb_strlen($texto) > $limit ? mb_substr($texto, 0, $limit).'...' : $texto;
    }

    public function create(): void
    {
        $this->closeVisualizar();
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $ordem = $this->findOrdem($id);

        $this->closeVisualizar();
        $this->editingId = $ordem['id'];
        $this->cliente_id = $ordem['cliente_id'];
        $this->tipo = $ordem['tipo'];
        $this->titulo = $ordem['titulo'];
        $this->descricao = $ordem['descricao'] ?? '';
        $this->data_agendada = $ordem['data_agendada'] ?? '';
        $this->hora_agendada = $ordem['hora_agendada'] ?? '';
        $this->participante = $ordem['participante'] ?? '';
        $this->participante_telefone = $ordem['participante_telefone'] ?? '';
        $this->tecnico_id = $ordem['tecnico_id'] ?? null;
        $this->status = $ordem['status'];
        $this->showForm = true;
    }

    public function save(): void
    {
        if (blank($this->tecnico_id)) {
            $this->tecnico_id = null;
        }

        $data = $this->validate();
        $data['data_agendada'] = $data['data_agendada'] ?: null;
        $data['hora_agendada'] = $data['data_agendada']
            ? OrdemServicoRepository::normalizeHoraAgendada($data['hora_agendada'] ?? null)
            : null;
        $data['tecnico_id'] = $data['tecnico_id'] ?: null;

        if ($this->editingId) {
            OrdemServicoRepository::updateFromForm($this->editingId, $data);
            $this->notification()->success('Ordem atualizada', 'A ordem de serviço foi salva com sucesso.');
        } else {
            OrdemServicoRepository::createFromForm($data);
            $this->notification()->success('Ordem cadastrada', 'A ordem de serviço foi criada com sucesso.');
        }

        $this->carregarOrdens();
        $this->resetForm();
        $this->showForm = false;
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(int $id): void
    {
        OrdemServicoRepository::delete($id);
        $this->carregarOrdens();

        if ($this->visualizarOrdem && $this->visualizarOrdem['id'] === $id) {
            $this->closeVisualizar();
            $this->visualizarOrdem = null;
        }

        if ($this->runningOrdemId === $id) {
            $this->runningOrdemId = null;
            $this->runningOrdemStartedAt = null;
        }

        $this->notification()->success('Ordem removida', 'A ordem de serviço foi excluída.');
    }

    public function ordemPausada(array $ordem): bool
    {
        return ($ordem['pausada'] ?? false)
            && $ordem['status'] === OrdemServicoStatus::Pendente->value;
    }

    public function ordemEmExecucao(int $ordemId): bool
    {
        return $this->runningOrdemId === $ordemId;
    }

    public function tempoOrdemAtual(int $segundos, ?int $ordemId = null): string
    {
        return $this->formatarTempo($this->segundosOrdemAtual($segundos, $ordemId));
    }

    public function segundosOrdemAtual(int $segundos, ?int $ordemId = null): int
    {
        $total = $segundos;

        if ($ordemId && $this->runningOrdemId === $ordemId && $this->runningOrdemStartedAt) {
            $total += now()->timestamp - $this->runningOrdemStartedAt;
        }

        return $total;
    }

    private function formatarTempo(int $segundos): string
    {
        $horas = intdiv($segundos, 3600);
        $minutos = intdiv($segundos % 3600, 60);
        $segundosRestantes = $segundos % 60;

        return sprintf('%02d:%02d:%02d', $horas, $minutos, $segundosRestantes);
    }

    private function parseTempo(string $tempo): ?int
    {
        if (! preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', trim($tempo), $partes)) {
            return null;
        }

        $horas = (int) $partes[1];
        $minutos = (int) $partes[2];
        $segundos = (int) $partes[3];

        if ($minutos > 59 || $segundos > 59) {
            return null;
        }

        return ($horas * 3600) + ($minutos * 60) + $segundos;
    }

    private function acumularTempoOrdem(int $id): void
    {
        if ($this->runningOrdemId !== $id || ! $this->runningOrdemStartedAt) {
            return;
        }

        $index = $this->findOrdemIndex($id);
        $this->ordens[$index]['tempo_segundos'] += now()->timestamp - $this->runningOrdemStartedAt;
        $this->runningOrdemId = null;
        $this->runningOrdemStartedAt = null;
        $this->persistOrdemIndex($index);
    }

    private function pararTimerOutraOrdem(?int $exceptId = null): void
    {
        if ($this->runningOrdemId && $this->runningOrdemId !== $exceptId) {
            $this->acumularTempoOrdem($this->runningOrdemId);
        }
    }

    private function syncVisualizar(int $index): void
    {
        $this->ordens[$index] = $this->normalizeOrdem($this->ordens[$index]);

        if ($this->visualizarOrdem && $this->visualizarOrdem['id'] === $this->ordens[$index]['id']) {
            $this->visualizarOrdem = $this->ordens[$index];
            $this->carregarCamposExecucao();
        }
    }

    private function carregarCamposExecucao(): void
    {
        if (! $this->visualizarOrdem) {
            return;
        }

        $this->execDescricaoServicos = $this->visualizarOrdem['descricao_servicos'] ?? '';
        $this->carregarParticipantesExecucao($this->visualizarOrdem['participantes'] ?? []);
        $this->execObservacoes = $this->visualizarOrdem['observacoes'] ?? '';
    }

    private function persistirCamposExecucao(): void
    {
        if (! $this->visualizarOrdem) {
            return;
        }

        $index = $this->findOrdemIndex($this->visualizarOrdem['id']);
        $this->ordens[$index]['descricao_servicos'] = $this->execDescricaoServicos;
        $this->ordens[$index]['participantes'] = $this->montarParticipantesExecucao();
        $this->ordens[$index]['observacoes'] = $this->execObservacoes;
        $this->persistOrdemIndex($index);
        $this->syncVisualizar($index);
    }

    public function updatedExecDescricaoServicos(): void
    {
        $this->persistirCamposExecucao();
    }

    public function updatedExecObservacoes(): void
    {
        $this->persistirCamposExecucao();
    }

    public function iniciarOrdem(int $id): void
    {
        $ordem = $this->findOrdem($id);

        if (in_array($ordem['status'], [OrdemServicoStatus::Concluida->value, OrdemServicoStatus::Cancelada->value], true)) {
            return;
        }

        if ($this->runningOrdemId === $id) {
            return;
        }

        $this->pararTimerOutraOrdem($id);
        $index = $this->findOrdemIndex($id);

        if (! $this->ordens[$index]['iniciada_em']) {
            $this->ordens[$index]['iniciada_em'] = now()->toDateTimeString();
        }

        $this->ordens[$index]['status'] = OrdemServicoStatus::EmAndamento->value;
        $this->ordens[$index]['pausada'] = false;
        $this->ordens[$index]['finalizada_em'] = null;
        $this->runningOrdemId = $id;
        $this->runningOrdemStartedAt = now()->timestamp;
        $this->persistOrdemIndex($index);
        $this->syncVisualizar($index);

        $this->notification()->success('Ordem iniciada', 'O cronômetro foi acionado e o status mudou para Em andamento.');
    }

    public function solicitarPausa(int $id): void
    {
        if ($this->runningOrdemId !== $id) {
            return;
        }

        $this->pausandoOrdemId = $id;
        $this->motivoPausa = '';
        $this->resetValidation('motivoPausa');
    }

    public function confirmarPausa(): void
    {
        if (! $this->pausandoOrdemId) {
            return;
        }

        $this->validate([
            'motivoPausa' => ['required', 'string', 'max:1000'],
        ]);

        $id = $this->pausandoOrdemId;

        if ($this->runningOrdemId !== $id) {
            $this->cancelarPausa();

            return;
        }

        $this->acumularTempoOrdem($id);
        $index = $this->findOrdemIndex($id);
        $this->ordens[$index]['status'] = OrdemServicoStatus::Pendente->value;
        $this->ordens[$index]['pausada'] = true;
        OrdemServicoRepository::addPausa($id, trim($this->motivoPausa));
        $this->persistOrdemIndex($index);
        $this->syncVisualizar($index);
        $this->cancelarPausa();

        $this->notification()->send([
            'icon' => 'warning',
            'title' => 'Ordem pausada',
            'description' => 'O motivo foi registrado e o cronômetro foi interrompido.',
            'timeout' => 3000,
        ]);
    }

    public function cancelarPausa(): void
    {
        $this->pausandoOrdemId = null;
        $this->motivoPausa = '';
        $this->resetValidation('motivoPausa');
    }

    public function solicitarFinalizacao(int $id): void
    {
        $ordem = $this->findOrdem($id);

        if (in_array($ordem['status'], [OrdemServicoStatus::Concluida->value, OrdemServicoStatus::Cancelada->value], true)) {
            return;
        }

        $this->finalizacaoRetomarTimer = $this->runningOrdemId === $id;

        if ($this->finalizacaoRetomarTimer) {
            $this->acumularTempoOrdem($id);
            $ordem = $this->findOrdem($id);
        }

        $this->finalizandoOrdemId = $id;
        $this->execDescricaoServicos = $ordem['descricao_servicos'] ?? '';
        $this->carregarParticipantesExecucao($ordem['participantes'] ?? []);
        $this->execObservacoes = $ordem['observacoes'] ?? '';
        $this->execTempoTotal = $this->formatarTempo($ordem['tempo_segundos'] ?? 0);
        $this->resetValidation([
            'execDescricaoServicos',
            'execParticipante1',
            'execParticipante2',
            'execParticipante3',
            'execParticipante4',
            'execObservacoes',
            'execTempoTotal',
        ]);
    }

    public function confirmarFinalizacao(): void
    {
        if (! $this->finalizandoOrdemId) {
            return;
        }

        $this->validate([
            'execDescricaoServicos' => ['required', 'string', 'max:10000'],
            'execParticipante1' => ['nullable', 'string', 'max:255'],
            'execParticipante2' => ['nullable', 'string', 'max:255'],
            'execParticipante3' => ['nullable', 'string', 'max:255'],
            'execParticipante4' => ['nullable', 'string', 'max:255'],
            'execObservacoes' => ['nullable', 'string', 'max:10000'],
            'execTempoTotal' => ['required', 'regex:/^\d{1,2}:\d{2}:\d{2}$/'],
        ]);

        $descricaoTexto = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', $this->execDescricaoServicos)));

        if ($descricaoTexto === '') {
            $this->addError('execDescricaoServicos', 'Informe a descrição dos serviços.');

            return;
        }

        $segundos = $this->parseTempo($this->execTempoTotal);

        if ($segundos === null) {
            $this->addError('execTempoTotal', 'Informe um tempo válido no formato HH:MM:SS.');

            return;
        }

        $id = $this->finalizandoOrdemId;

        if ($this->runningOrdemId === $id) {
            $this->runningOrdemId = null;
            $this->runningOrdemStartedAt = null;
        }

        $index = $this->findOrdemIndex($id);
        $this->ordens[$index]['descricao_servicos'] = $this->execDescricaoServicos;
        $this->ordens[$index]['participantes'] = $this->montarParticipantesExecucao();
        $this->ordens[$index]['observacoes'] = $this->execObservacoes;
        $this->ordens[$index]['tempo_segundos'] = $segundos;
        $this->ordens[$index]['status'] = OrdemServicoStatus::Concluida->value;
        $this->ordens[$index]['pausada'] = false;
        $this->ordens[$index]['finalizada_em'] = now()->toDateTimeString();
        $this->persistOrdemIndex($index);
        $this->syncVisualizar($index);
        $this->finalizacaoRetomarTimer = false;
        $this->cancelarFinalizacao();

        $this->notification()->success(
            'Ordem finalizada',
            'Tempo total: '.$this->formatarTempo($segundos),
        );
    }

    public function cancelarFinalizacao(): void
    {
        $id = $this->finalizandoOrdemId;
        $retomar = $this->finalizacaoRetomarTimer;

        $this->finalizandoOrdemId = null;
        $this->finalizacaoRetomarTimer = false;
        $this->reset([
            'execDescricaoServicos',
            'execParticipante1',
            'execParticipante2',
            'execParticipante3',
            'execParticipante4',
            'execObservacoes',
            'execTempoTotal',
        ]);
        $this->resetValidation([
            'execDescricaoServicos',
            'execParticipante1',
            'execParticipante2',
            'execParticipante3',
            'execParticipante4',
            'execObservacoes',
            'execTempoTotal',
        ]);

        if ($id && $retomar) {
            $ordem = $this->findOrdem($id);

            if ($ordem['status'] === OrdemServicoStatus::EmAndamento->value && ! ($ordem['pausada'] ?? false)) {
                $this->runningOrdemId = $id;
                $this->runningOrdemStartedAt = now()->timestamp;
            }
        }
    }

    public function visualizar(int $id): void
    {
        $this->showForm = false;
        $this->novoComentario = '';
        $this->visualizarOrdem = $this->findOrdem($id);
        $this->carregarCamposExecucao();
        $this->showVisualizar = true;
    }

    public function adicionarComentario(): void
    {
        if (! $this->visualizarOrdem) {
            return;
        }

        $texto = trim($this->novoComentario);

        if ($texto === '') {
            $this->notification()->send([
                'icon' => 'warning',
                'title' => 'Comentário vazio',
                'description' => 'Digite uma mensagem antes de enviar.',
                'timeout' => 3000,
            ]);

            return;
        }

        $index = $this->findOrdemIndex($this->visualizarOrdem['id']);
        OrdemServicoRepository::addComentario($this->visualizarOrdem['id'], $texto);
        $this->ordens[$index] = OrdemServicoRepository::findAsArray($this->visualizarOrdem['id']);
        $this->syncVisualizar($index);
        $this->novoComentario = '';

        $this->notification()->send([
            'icon' => 'success',
            'title' => 'Comentário adicionado',
            'timeout' => 3000,
        ]);
    }

    public function closeVisualizar(): void
    {
        $this->persistirCamposExecucao();
        $this->showVisualizar = false;
        $this->novoComentario = '';
        $this->reset(['execDescricaoServicos', 'execParticipante1', 'execParticipante2', 'execParticipante3', 'execParticipante4', 'execObservacoes']);
    }

    public function limparFiltros(): void
    {
        $this->reset(['busca', 'filtroStatus', 'filtroTipo', 'filtroCliente', 'filtroAgendamento', 'buscaClienteFiltro']);
    }

    public function temFiltrosAtivos(): bool
    {
        return $this->busca !== ''
            || $this->filtroStatus !== ''
            || $this->filtroTipo !== ''
            || $this->filtroCliente !== ''
            || $this->filtroAgendamento !== '';
    }

    /** @return array{label: string, class: string} */
    public function infoDataAgendada(?string $data, string $status): array
    {
        return OrdemServicoRepository::infoDataAgendada($data, $status);
    }

    public function formatAgendamento(?string $data, ?string $hora = null): string
    {
        return OrdemServicoRepository::formatAgendamento($data, $hora);
    }

    public function formatDataListagem(?string $data): string
    {
        return OrdemServicoRepository::formatDataListagem($data);
    }

    public function formatHoraListagem(?string $data, ?string $hora): ?string
    {
        return OrdemServicoRepository::formatHoraListagem($data, $hora);
    }

    /** @return array{segundos: int, running: bool, startedAt: int|null, dotClass: string, textClass: string, label: string} */
    public function infoCronometroListagem(array $ordem): array
    {
        $segundos = (int) ($ordem['tempo_segundos'] ?? 0);
        $emExecucao = $this->ordemEmExecucao($ordem['id']);
        $pausada = $this->ordemPausada($ordem);
        $status = $ordem['status'];

        if ($status === OrdemServicoStatus::Concluida->value) {
            return [
                'segundos' => $segundos,
                'running' => false,
                'startedAt' => null,
                'dotClass' => 'bg-emerald-500',
                'textClass' => 'text-emerald-700',
                'label' => 'Concluída',
            ];
        }

        if ($status === OrdemServicoStatus::Cancelada->value) {
            return [
                'segundos' => $segundos,
                'running' => false,
                'startedAt' => null,
                'dotClass' => 'bg-red-400',
                'textClass' => 'text-red-500',
                'label' => 'Cancelada',
            ];
        }

        if ($emExecucao) {
            return [
                'segundos' => $segundos,
                'running' => true,
                'startedAt' => $this->runningOrdemStartedAt,
                'dotClass' => 'bg-blue-500 animate-pulse',
                'textClass' => 'text-blue-700',
                'label' => 'Em execução',
            ];
        }

        if ($pausada) {
            return [
                'segundos' => $segundos,
                'running' => false,
                'startedAt' => null,
                'dotClass' => 'bg-amber-500',
                'textClass' => 'text-amber-700',
                'label' => 'Pausada',
            ];
        }

        if ($status === OrdemServicoStatus::EmAndamento->value) {
            return [
                'segundos' => $segundos,
                'running' => false,
                'startedAt' => null,
                'dotClass' => 'bg-orange-400',
                'textClass' => 'text-orange-600',
                'label' => 'Interrompida',
            ];
        }

        if ($segundos > 0) {
            return [
                'segundos' => $segundos,
                'running' => false,
                'startedAt' => null,
                'dotClass' => 'bg-slate-400',
                'textClass' => 'text-slate-600',
                'label' => 'Parado',
            ];
        }

        return [
            'segundos' => 0,
            'running' => false,
            'startedAt' => null,
            'dotClass' => 'bg-slate-300',
            'textClass' => 'text-slate-400',
            'label' => 'Aguardando',
        ];
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId',
            'cliente_id',
            'tipo',
            'titulo',
            'descricao',
            'data_agendada',
            'hora_agendada',
            'participante',
            'participante_telefone',
            'tecnico_id',
            'buscaClienteForm',
        ]);
        $this->status = OrdemServicoStatus::Pendente->value;
        $this->resetValidation();
    }

    /** @return array<int, array<string, mixed>> */
    private function ordensFiltradas(): array
    {
        $busca = mb_strtolower(trim($this->busca));

        return collect($this->ordens)
            ->filter(function (array $ordem) use ($busca): bool {
                if ($this->filtroStatus !== '' && $ordem['status'] !== $this->filtroStatus) {
                    return false;
                }

                if ($this->filtroTipo !== '' && $ordem['tipo'] !== $this->filtroTipo) {
                    return false;
                }

                if ($this->filtroCliente !== '' && (string) $ordem['cliente_id'] !== $this->filtroCliente) {
                    return false;
                }

                if ($this->filtroAgendamento !== '' && ($ordem['data_agendada'] ?? '') !== $this->filtroAgendamento) {
                    return false;
                }

                if ($busca === '') {
                    return true;
                }

                $cliente = mb_strtolower($this->nomeCliente($ordem['cliente_id']));
                $tipo = mb_strtolower(OrdemServicoTipo::from($ordem['tipo'])->label());
                $status = mb_strtolower(OrdemServicoStatus::from($ordem['status'])->label());
                $descricao = mb_strtolower(strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', $ordem['descricao'] ?? '')));

                return str_contains(mb_strtolower($ordem['titulo']), $busca)
                    || str_contains($cliente, $busca)
                    || str_contains($tipo, $busca)
                    || str_contains($status, $busca)
                    || str_contains($descricao, $busca)
                    || str_contains((string) $ordem['id'], $busca);
            })
            ->sortBy(function (array $ordem): array {
                $data = $ordem['data_agendada'] ?? '9999-12-31';

                return [$data, -$ordem['id']];
            })
            ->values()
            ->all();
    }

    public function with(): array
    {
        return [
            'ordensLista' => $this->ordensFiltradas(),
            'totalOrdens' => count($this->ordens),
            'clientes' => $this->clientesAtivos(),
            'clientesFormulario' => $this->clientesFormulario(),
            'clientesFiltroBusca' => $this->clientesFiltroBusca(),
            'tipos' => OrdemServicoTipo::options(),
            'statuses' => OrdemServicoStatus::options(),
        ];
    }
};
?>

<div>
    @if ($showForm)
        <div class="mx-auto max-w-3xl">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-slate-900">
                    {{ $editingId ? 'Editar Ordem de Serviço' : 'Nova Ordem de Serviço' }}
                </h2>
                <p class="text-sm text-slate-500">Informe o cliente e o tipo da ordem de serviço.</p>
            </div>

            @if (empty($clientes))
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-center">
                    <p class="font-medium text-amber-800">Nenhum cliente cadastrado</p>
                    <p class="mt-1 text-sm text-amber-700">Cadastre um cliente antes de criar uma ordem de serviço.</p>
                    <div class="mt-4">
                        <x-button primary label="Ir para Clientes" href="{{ route('clientes.index') }}" />
                    </div>
                </div>
            @else
                <form wire:submit="save" class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Cliente</label>

                            @if ($cliente_id)
                                @php
                                    $clienteSelecionado = collect($clientes)->firstWhere('id', $cliente_id);
                                @endphp
                                <div class="flex items-start justify-between gap-3 rounded-xl border border-brand-200 bg-brand-50/50 p-4">
                                    <div class="min-w-0">
                                        <p class="font-medium text-slate-900">{{ $clienteSelecionado['nome'] ?? '—' }}</p>
                                        <p class="mt-0.5 text-xs text-slate-600">{{ $clienteSelecionado['documento'] ?? 'Sem CNPJ' }}</p>
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="alterarCliente"
                                        class="shrink-0 text-sm font-medium text-brand-600 hover:text-brand-700"
                                    >
                                        Alterar
                                    </button>
                                </div>
                            @else
                                <x-input
                                    wire:model.live.debounce.300ms="buscaClienteForm"
                                    icon="magnifying-glass"
                                    placeholder="Razão social ou CNPJ"
                                />

                                @if ($buscaClienteForm !== '')
                                    <div class="mt-3 max-h-52 space-y-2 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-2">
                                        @forelse ($clientesFormulario as $cliente)
                                            <button
                                                type="button"
                                                wire:key="cliente-form-{{ $cliente['id'] }}"
                                                wire:click="selecionarCliente({{ $cliente['id'] }})"
                                                class="flex w-full items-start gap-3 rounded-lg border border-transparent bg-white p-3 text-left transition hover:border-brand-200 hover:bg-brand-50/50"
                                            >
                                                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-700">
                                                    {{ strtoupper(substr($cliente['nome'], 0, 1)) }}
                                                </span>
                                                <span class="min-w-0">
                                                    <span class="block truncate text-sm font-medium text-slate-900">{{ $cliente['nome'] }}</span>
                                                    <span class="mt-0.5 block text-xs text-slate-500">{{ $cliente['documento'] ?: 'Sem CNPJ' }}</span>
                                                </span>
                                            </button>
                                        @empty
                                            <div class="px-4 py-8 text-center">
                                                <x-icon name="magnifying-glass" class="mx-auto h-7 w-7 text-slate-300" />
                                                <p class="mt-2 text-sm text-slate-600">Nenhum cliente encontrado</p>
                                                <p class="mt-1 text-xs text-slate-500">Tente buscar por outro nome ou CNPJ.</p>
                                            </div>
                                        @endforelse
                                    </div>
                                @else
                                    <p class="mt-2 text-xs text-slate-500">Digite o nome ou CNPJ para buscar e selecionar o cliente.</p>
                                @endif
                            @endif

                            @error('cliente_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <x-native-select wire:model="tipo" label="Tipo">
                            <option value="">Selecione o tipo</option>
                            @foreach ($tipos as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-native-select>

                        @if ($editingId)
                            <x-native-select wire:model="status" label="Status">
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-native-select>
                        @else
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Status inicial</label>
                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700">
                                    Pendente — o técnico iniciará a execução após o cadastro.
                                </div>
                            </div>
                        @endif

                        <div class="sm:col-span-2">
                            <x-input wire:model="titulo" label="Título" placeholder="Ex: Manutenção preventiva - Cliente ABC" />
                        </div>

                        <x-input
                            wire:model="participante"
                            label="Participante"
                            placeholder="Nome do cliente ou funcionário da empresa"
                        />

                        <x-phone
                            wire:model="participante_telefone"
                            label="Telefone do participante"
                            :mask="['(##) ####-####', '(##) #####-####']"
                            emit-formatted
                            placeholder="(11) 99999-9999"
                        />

                        <x-native-select wire:model="tecnico_id" label="Técnico">
                            <option value="">Selecione o técnico</option>
                            @foreach ($tecnicosDisponiveis as $tecnico)
                                <option value="{{ $tecnico['id'] }}">{{ $tecnico['nome'] }}</option>
                            @endforeach
                        </x-native-select>

                        <div class="sm:col-span-2" wire:key="ordem-descricao-{{ $editingId ?? 'nova' }}">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Descrição</label>
                            <div
                                wire:ignore
                                x-data="richTextEditor(@entangle('descricao').live)"
                                class="rich-text-editor overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                            >
                                <div class="flex flex-wrap gap-1 border-b border-slate-200 bg-slate-50 p-2">
                                    <button type="button" title="Negrito" x-on:click.prevent="exec('bold')" class="rich-text-editor__btn">
                                        <x-icon name="bold" class="h-4 w-4" />
                                    </button>
                                    <button type="button" title="Itálico" x-on:click.prevent="exec('italic')" class="rich-text-editor__btn">
                                        <x-icon name="italic" class="h-4 w-4" />
                                    </button>
                                    <button type="button" title="Sublinhado" x-on:click.prevent="exec('underline')" class="rich-text-editor__btn">
                                        <x-icon name="underline" class="h-4 w-4" />
                                    </button>
                                    <span class="mx-1 w-px self-stretch bg-slate-200"></span>
                                    <button type="button" title="Lista com marcadores" x-on:click.prevent="exec('insertUnorderedList')" class="rich-text-editor__btn">
                                        <x-icon name="list-bullet" class="h-4 w-4" />
                                    </button>
                                    <button type="button" title="Lista numerada" x-on:click.prevent="exec('insertOrderedList')" class="rich-text-editor__btn">
                                        <x-icon name="numbered-list" class="h-4 w-4" />
                                    </button>
                                    <span class="mx-1 w-px self-stretch bg-slate-200"></span>
                                    <button type="button" title="Remover formatação" x-on:click.prevent="exec('removeFormat')" class="rich-text-editor__btn px-2 text-xs font-medium">
                                        Limpar
                                    </button>
                                </div>
                                <div
                                    x-ref="editor"
                                    contenteditable="true"
                                    x-on:input="sync()"
                                    x-on:blur="sync()"
                                    data-placeholder="Detalhes da ordem de serviço..."
                                    class="rich-text-editor__content min-h-[8rem] px-4 py-3 text-sm text-slate-900 outline-none"
                                ></div>
                            </div>
                            @error('descricao')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:col-span-2 sm:grid-cols-2">
                            <x-input wire:model="data_agendada" label="Data agendada" type="date" />
                            <x-input wire:model="hora_agendada" label="Hora agendada (opcional)" type="time" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <x-button flat label="Cancelar" wire:click="cancel" />
                        <x-button primary type="submit" label="{{ $editingId ? 'Salvar alterações' : 'Cadastrar ordem' }}" />
                    </div>
                </form>
            @endif
        </div>
    @else
        <div
            class="mb-4 space-y-3"
            x-data="{ filtrosAbertos: false }"
        >
            <div class="page-list-toolbar-row">
                <div class="min-w-0">
                    <x-input
                        wire:model.live.debounce.300ms="busca"
                        icon="magnifying-glass"
                        label="Pesquisar"
                        placeholder="OS, título, cliente ou descrição"
                    />
                </div>

                <button
                    type="button"
                    x-on:click="filtrosAbertos = ! filtrosAbertos"
                    class="inline-flex h-10 shrink-0 items-center gap-2 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    <x-icon name="plus" class="h-4 w-4 transition-transform" x-bind:class="filtrosAbertos && 'rotate-45'" />
                    Filtros
                </button>

                <x-button primary icon="plus" label="Nova Ordem" wire:click="create" class="!w-auto shrink-0 whitespace-nowrap" />
            </div>

            <div
                x-show="filtrosAbertos"
                x-transition
                x-cloak
                class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
            >
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <x-native-select wire:model.live="filtroStatus" label="Status">
                        <option value="">Todos</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-native-select>

                    <x-native-select wire:model.live="filtroTipo" label="Tipo">
                        <option value="">Todos</option>
                        @foreach ($tipos as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-native-select>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Cliente</label>

                        @if ($filtroCliente !== '')
                            @php
                                $clienteFiltroSelecionado = collect($clientes)->firstWhere('id', (int) $filtroCliente);
                            @endphp
                            <div class="flex items-start justify-between gap-3 rounded-xl border border-brand-200 bg-brand-50/50 px-3 py-2.5">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-900">{{ $clienteFiltroSelecionado['nome'] ?? '—' }}</p>
                                    <p class="truncate text-xs text-slate-600">{{ $clienteFiltroSelecionado['documento'] ?? 'Sem CNPJ' }}</p>
                                </div>
                                <button
                                    type="button"
                                    wire:click="limparClienteFiltro"
                                    class="shrink-0 text-sm font-medium text-brand-600 hover:text-brand-700"
                                >
                                    Limpar
                                </button>
                            </div>
                        @else
                            <x-input
                                wire:model.live.debounce.300ms="buscaClienteFiltro"
                                icon="magnifying-glass"
                                placeholder="Nome ou CNPJ"
                            />

                            @if ($buscaClienteFiltro !== '')
                                <div class="mt-2 max-h-40 space-y-1 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-2">
                                    @forelse ($clientesFiltroBusca as $cliente)
                                        <button
                                            type="button"
                                            wire:key="cliente-filtro-{{ $cliente['id'] }}"
                                            wire:click="selecionarClienteFiltro({{ $cliente['id'] }})"
                                            class="flex w-full items-start gap-2 rounded-lg border border-transparent bg-white px-2.5 py-2 text-left transition hover:border-brand-200 hover:bg-brand-50/50"
                                        >
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-medium text-slate-900">{{ $cliente['nome'] }}</span>
                                                <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $cliente['documento'] ?: 'Sem CNPJ' }}</span>
                                            </span>
                                        </button>
                                    @empty
                                        <p class="px-2 py-4 text-center text-sm text-slate-600">Nenhum cliente encontrado.</p>
                                    @endforelse
                                </div>
                            @else
                                <p class="mt-1 text-xs text-slate-500">Digite para buscar por nome ou CNPJ.</p>
                            @endif
                        @endif
                    </div>

                    <x-input wire:model.live="filtroAgendamento" label="Agendamento" type="date" />
                </div>

                @if ($busca || $filtroStatus || $filtroTipo || $filtroCliente || $filtroAgendamento)
                    <div class="mt-4 flex justify-end border-t border-slate-100 pt-4">
                        <x-button flat label="Limpar filtros" wire:click="limparFiltros" />
                    </div>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="w-16 px-3 py-2.5">OS</th>
                            <th class="px-3 py-2.5">Cliente</th>
                            <th class="px-3 py-2.5">Serviço</th>
                            <th class="whitespace-nowrap px-3 py-2.5">Agendamento</th>
                            <th class="whitespace-nowrap px-3 py-2.5">Cronômetro</th>
                            <th class="whitespace-nowrap px-3 py-2.5">Status</th>
                            <th class="w-[5.5rem] whitespace-nowrap px-3 py-2.5 text-center">Exec.</th>
                            <th class="w-24 whitespace-nowrap px-3 py-2.5 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($ordensLista as $ordem)
                            @php
                                $tipoEnum = \App\Enums\OrdemServicoTipo::from($ordem['tipo']);
                                $statusEnum = \App\Enums\OrdemServicoStatus::from($ordem['status']);
                                $dataInfo = $this->infoDataAgendada($ordem['data_agendada'], $ordem['status']);
                                $descricaoResumo = $this->descricaoResumo($ordem['descricao'], 90);
                                $pausada = $this->ordemPausada($ordem);
                                $emExecucao = $this->ordemEmExecucao($ordem['id']);
                                $finalizadaLista = in_array($ordem['status'], [\App\Enums\OrdemServicoStatus::Concluida->value, \App\Enums\OrdemServicoStatus::Cancelada->value], true);
                                $canIniciar = ! $finalizadaLista && ! $emExecucao && in_array($ordem['status'], [\App\Enums\OrdemServicoStatus::Pendente->value, \App\Enums\OrdemServicoStatus::EmAndamento->value], true);
                                $canPausar = $emExecucao;
                                $canParar = ! $finalizadaLista && ($emExecucao || $pausada || ($ordem['tempo_segundos'] ?? 0) > 0);
                                $cronometroInfo = $this->infoCronometroListagem($ordem);
                            @endphp
                            <tr
                                wire:key="ordem-{{ $ordem['id'] }}"
                                @class([
                                    'border-l-[3px] hover:bg-slate-50/80',
                                    'bg-amber-50/70 ring-1 ring-inset ring-amber-200' => $pausada,
                                    'bg-blue-50/40' => $emExecucao,
                                ])
                                style="border-left-color: {{ $tipoEnum->color() }}"
                            >
                                <td class="px-3 py-2.5">
                                    <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-bold tracking-wide text-slate-800 ring-1 ring-slate-200">
                                        {{ str_pad((string) $ordem['id'], 3, '0', STR_PAD_LEFT) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5">
                                    <p class="truncate font-medium text-slate-900">{{ $this->nomeCliente($ordem['cliente_id']) }}</p>
                                    <p class="truncate text-xs text-slate-500">
                                        {{ $this->documentoCliente($ordem['cliente_id']) ?: 'Sem CNPJ' }}
                                    </p>
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="flex min-w-0 items-center gap-2">
                                        <span
                                            class="h-2 w-2 shrink-0 rounded-full"
                                            style="background-color: {{ $tipoEnum->color() }}"
                                        ></span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-slate-800">{{ $tipoEnum->label() }}</p>
                                            @if ($descricaoResumo)
                                                <p class="truncate text-xs text-slate-500" title="{{ $descricaoResumo }}">{{ $descricaoResumo }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5">
                                    <p class="text-sm font-medium text-slate-800">
                                        {{ $this->formatDataListagem($ordem['data_agendada'] ?? null) }}
                                    </p>
                                    @if ($ordem['data_agendada'] ?? null)
                                        <p class="text-xs font-medium text-slate-500">
                                            {{ $this->formatHoraListagem($ordem['data_agendada'], $ordem['hora_agendada'] ?? null) }}
                                        </p>
                                    @endif
                                    <p @class(['text-xs font-medium', $dataInfo['class']])>
                                        {{ $dataInfo['label'] }}
                                    </p>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5">
                                    <x-ordem-cronometro
                                        wire:key="cronometro-{{ $ordem['id'] }}-{{ $cronometroInfo['running'] ? 'run' : 'stop' }}-{{ $cronometroInfo['segundos'] }}"
                                        :segundos="$cronometroInfo['segundos']"
                                        :running="$cronometroInfo['running']"
                                        :started-at="$cronometroInfo['startedAt']"
                                        :dot-class="$cronometroInfo['dotClass']"
                                        :text-class="$cronometroInfo['textClass']"
                                        :label="$cronometroInfo['label']"
                                    />
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5">
                                    @php
                                        $ultimaPausa = collect($ordem['pausas'] ?? [])->last();
                                        $statusTitle = $pausada && $ultimaPausa
                                            ? 'Pausada em '.\Illuminate\Support\Carbon::parse($ultimaPausa['em'])->format('d/m/Y H:i').': '.$ultimaPausa['motivo']
                                            : $statusEnum->label();
                                    @endphp
                                    <span
                                        title="{{ $statusTitle }}"
                                        class="inline-flex max-w-[9rem] items-center gap-1.5 truncate rounded-full bg-white py-0.5 pr-2 pl-1 ring-1 ring-slate-200"
                                    >
                                        <span
                                            class="h-2 w-2 shrink-0 rounded-full"
                                            style="background-color: {{ $statusEnum->color() }}"
                                        ></span>
                                        <span class="truncate text-xs font-medium text-slate-800">{{ $statusEnum->label() }}</span>
                                        @if ($pausada)
                                            <x-icon name="pause" class="h-3 w-3 shrink-0 text-amber-600" />
                                        @elseif ($emExecucao)
                                            <span class="h-1.5 w-1.5 shrink-0 animate-pulse rounded-full bg-blue-500"></span>
                                        @endif
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-center">
                                    @if (! $finalizadaLista && ($canIniciar || $canPausar || $canParar))
                                        <div class="inline-flex items-center rounded-md border border-slate-200 bg-white p-0.5 shadow-sm">
                                            @if ($canIniciar)
                                                <button
                                                    type="button"
                                                    wire:click="iniciarOrdem({{ $ordem['id'] }})"
                                                    title="{{ $pausada || $ordem['status'] === \App\Enums\OrdemServicoStatus::EmAndamento->value ? 'Retomar' : 'Iniciar' }}"
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded text-emerald-600 hover:bg-emerald-50"
                                                >
                                                    <x-icon name="play" class="h-3.5 w-3.5" />
                                                </button>
                                            @endif

                                            @if ($canPausar)
                                                <button
                                                    type="button"
                                                    wire:click="solicitarPausa({{ $ordem['id'] }})"
                                                    title="Pausar"
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded text-amber-600 hover:bg-amber-50"
                                                >
                                                    <x-icon name="pause" class="h-3.5 w-3.5" />
                                                </button>
                                            @endif

                                            @if ($canParar)
                                                <button
                                                    type="button"
                                                    wire:click="solicitarFinalizacao({{ $ordem['id'] }})"
                                                    title="Parar"
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded text-red-600 hover:bg-red-50"
                                                >
                                                    <x-icon name="stop" class="h-3.5 w-3.5" />
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5">
                                    <div class="flex justify-end gap-1">
                                        <button type="button" wire:click="visualizar({{ $ordem['id'] }})" title="Visualizar" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-600 ring-1 ring-slate-200 hover:bg-slate-100 hover:text-brand-600">
                                            <x-icon name="eye" class="h-3.5 w-3.5" />
                                        </button>

                                        <button type="button" wire:click="edit({{ $ordem['id'] }})" title="Editar" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-600 ring-1 ring-slate-200 hover:bg-slate-100 hover:text-brand-600">
                                            <x-icon name="pencil" class="h-3.5 w-3.5" />
                                        </button>

                                        <button type="button" wire:click="delete({{ $ordem['id'] }})" wire:confirm="Deseja excluir esta ordem de serviço?" title="Excluir" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-red-600 ring-1 ring-red-200 hover:bg-red-50">
                                            <x-icon name="trash" class="h-3.5 w-3.5" />
                                        </button>

                                        <x-table-action-menu title="Mais opções" wire:key="ordem-menu-{{ $ordem['id'] }}">
                                            <button
                                                type="button"
                                                x-on:click="close()"
                                                wire:click.stop="gerarPdf({{ $ordem['id'] }})"
                                                class="flex w-full items-center rounded-md px-3 py-2 text-sm text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900"
                                            >
                                                <x-icon name="document-arrow-down" class="mr-2 h-5 w-5 shrink-0" />
                                                Gerar PDF
                                            </button>
                                        </x-table-action-menu>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-12 text-center text-slate-600">
                                    @if ($this->temFiltrosAtivos())
                                        Nenhuma ordem encontrada com os filtros aplicados.
                                    @else
                                        Nenhuma ordem de serviço cadastrada. Clique em "Nova Ordem" para começar.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t border-slate-100 bg-slate-50">
                        <tr>
                            <td colspan="8" class="px-5 py-3 text-sm text-slate-600">
                                @if ($this->temFiltrosAtivos())
                                    Exibindo {{ count($ordensLista) }} de {{ $totalOrdens }} {{ $totalOrdens === 1 ? 'ordem cadastrada' : 'ordens cadastradas' }}
                                @else
                                    {{ $totalOrdens }} {{ $totalOrdens === 1 ? 'ordem cadastrada' : 'ordens cadastradas' }}
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
        </div>
    @endif

    @if ($visualizarOrdem)
        @php
            $ordemView = $visualizarOrdem;
            $tipoView = \App\Enums\OrdemServicoTipo::from($ordemView['tipo']);
            $statusView = \App\Enums\OrdemServicoStatus::from($ordemView['status']);
            $dataView = $this->infoDataAgendada($ordemView['data_agendada'], $ordemView['status']);
            $pausadaView = $this->ordemPausada($ordemView);
            $emExecucaoView = $this->ordemEmExecucao($ordemView['id']);
            $finalizada = in_array($ordemView['status'], [\App\Enums\OrdemServicoStatus::Concluida->value, \App\Enums\OrdemServicoStatus::Cancelada->value], true);
        @endphp
        <div
            wire:key="visualizar-ordem-drawer"
            x-data="{ open: @entangle('showVisualizar').live }"
            x-cloak
            x-show="open"
            x-on:keydown.escape.window="open && $wire.closeVisualizar()"
            class="fixed inset-0 z-[90]"
            style="display: none;"
            role="dialog"
            aria-modal="true"
        >
            <button
                type="button"
                x-show="open"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0 bg-slate-900/40 backdrop-blur-[1px]"
                x-on:click="$wire.closeVisualizar()"
                aria-label="Fechar"
            ></button>

            <div
                x-show="open"
                x-transition:enter="transform transition ease-out duration-300"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in duration-200"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="absolute inset-y-0 right-0 flex w-full flex-col border-l border-slate-200 bg-white shadow-2xl lg:w-[calc(100%-16rem)]"
            >
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-slate-900">Visualizar Ordem de Serviço</h2>
                        <p class="mt-0.5 truncate text-sm text-slate-600">
                            #OS-{{ str_pad((string) $ordemView['id'], 3, '0', STR_PAD_LEFT) }} — {{ $ordemView['titulo'] }}
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="closeVisualizar"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                    >
                        <x-icon name="x-mark" class="h-4 w-4" />
                    </button>
                </div>

                @if ($pausadaView)
                    @php $ultimaPausaView = collect($ordemView['pausas'] ?? [])->last(); @endphp
                    <div class="shrink-0 border-b border-amber-200 bg-amber-50 px-6 py-2">
                        <p class="truncate text-xs font-medium text-amber-900">
                            <x-icon name="exclamation-triangle" class="mr-1 inline h-4 w-4 text-amber-600" />
                            Pausada
                            @if ($ultimaPausaView)
                                — {{ \Illuminate\Support\Carbon::parse($ultimaPausaView['em'])->format('d/m/Y H:i') }}: {{ $ultimaPausaView['motivo'] }}
                            @endif
                        </p>
                    </div>
                @endif

                <div class="flex min-h-0 flex-1 flex-col lg:flex-row">
                    <div class="min-h-0 flex-1 overflow-y-auto px-6 py-6 lg:border-r lg:border-slate-100">
                        @if (! $finalizada)
                            <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="font-mono text-lg font-semibold tabular-nums text-slate-900">
                                        {{ $this->tempoOrdemAtual($ordemView['tempo_segundos'] ?? 0, $ordemView['id']) }}
                                    </span>
                                    <span class="text-xs text-slate-500">Tempo de execução</span>
                                </div>
                                <div class="inline-flex items-center rounded-md border border-slate-200 bg-white p-0.5 shadow-sm">
                                    @if (! $emExecucaoView && in_array($ordemView['status'], [\App\Enums\OrdemServicoStatus::Pendente->value, \App\Enums\OrdemServicoStatus::EmAndamento->value], true))
                                        <button
                                            type="button"
                                            wire:click="iniciarOrdem({{ $ordemView['id'] }})"
                                            title="{{ $pausadaView || $ordemView['status'] === \App\Enums\OrdemServicoStatus::EmAndamento->value ? 'Retomar' : 'Iniciar' }}"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded text-emerald-600 hover:bg-emerald-50"
                                        >
                                            <x-icon name="play" class="h-3.5 w-3.5" />
                                        </button>
                                    @endif

                                    @if ($emExecucaoView)
                                        <button
                                            type="button"
                                            wire:click="solicitarPausa({{ $ordemView['id'] }})"
                                            title="Pausar"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded text-amber-600 hover:bg-amber-50"
                                        >
                                            <x-icon name="pause" class="h-3.5 w-3.5" />
                                        </button>
                                    @endif

                                    @if ($emExecucaoView || $pausadaView || ($ordemView['tempo_segundos'] ?? 0) > 0)
                                        <button
                                            type="button"
                                            wire:click="solicitarFinalizacao({{ $ordemView['id'] }})"
                                            title="Parar"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded text-red-600 hover:bg-red-50"
                                        >
                                            <x-icon name="stop" class="h-3.5 w-3.5" />
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="mb-5 flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3">
                                <span class="font-mono text-lg font-semibold tabular-nums text-emerald-900">
                                    {{ $this->tempoOrdemAtual($ordemView['tempo_segundos'] ?? 0) }}
                                </span>
                                <div class="text-xs text-emerald-700">
                                    <p class="font-medium">Tempo total registrado</p>
                                    @if ($ordemView['finalizada_em'])
                                        <p>Finalizada em {{ \Illuminate\Support\Carbon::parse($ordemView['finalizada_em'])->format('d/m/Y H:i') }}</p>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-sm font-medium text-gray-700">Título</label>
                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900">
                                    {{ $ordemView['titulo'] }}
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5">
                                    <span @class(['inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold', $statusView->badgeClass()])>
                                        {{ $statusView->label() }}
                                    </span>
                                    @if ($pausadaView)
                                        <span class="ml-2 inline-flex animate-pulse rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800 ring-1 ring-amber-300">Pausada</span>
                                    @endif
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Tipo</label>
                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5">
                                    <span class="inline-flex items-center gap-1.5 text-sm font-medium" style="color: {{ $tipoView->color() }}">
                                        <span class="h-2 w-2 rounded-full" style="background-color: {{ $tipoView->color() }}"></span>
                                        {{ $tipoView->label() }}
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Cliente</label>
                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900">
                                    {{ $this->nomeCliente($ordemView['cliente_id']) }}
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Participante</label>
                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900">
                                    {{ $ordemView['participante'] ?: '—' }}
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Telefone do participante</label>
                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900">
                                    {{ $ordemView['participante_telefone'] ?: '—' }}
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Técnico</label>
                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900">
                                    {{ $this->nomeTecnico($ordemView['tecnico_id'] ?? null) }}
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Agendamento</label>
                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900">
                                    {{ $this->formatAgendamento($ordemView['data_agendada'] ?? null, $ordemView['hora_agendada'] ?? null) }}
                                    @if ($ordemView['data_agendada'])
                                        <span @class(['ml-2 text-xs font-medium', $dataView['class']])>({{ $dataView['label'] }})</span>
                                    @endif
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-sm font-medium text-gray-700">Descrição do chamado</label>
                                <div class="rich-text-editor__content min-h-[4rem] rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900">
                                    @if ($ordemView['descricao'])
                                        {!! $ordemView['descricao'] !!}
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if ($ordemView['status'] === \App\Enums\OrdemServicoStatus::Concluida->value)
                            <div class="space-y-4 border-t border-slate-100 pt-6">
                                <h3 class="text-sm font-semibold text-slate-900">Registro da execução</h3>

                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Descrição dos Serviços</label>
                                    <div class="rich-text-editor__content min-h-[4rem] rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900">
                                        @if ($ordemView['descricao_servicos'])
                                            {!! $ordemView['descricao_servicos'] !!}
                                        @else
                                            —
                                        @endif
                                        <div class="mt-3 flex justify-end border-t border-slate-200 pt-2.5 text-right text-sm">
                                            <span class="text-xs text-slate-600">Tempo do Serviço:</span>
                                            <span class="ml-2 font-mono text-sm font-semibold text-slate-900">
                                                {{ $this->tempoOrdemAtual($ordemView['tempo_segundos'] ?? 0, $ordemView['id']) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700">Participantes</label>
                                    @php $participantesView = $this->participantesPreenchidos($ordemView['participantes'] ?? []); @endphp
                                    @if (count($participantesView) > 0)
                                        <div @class(['grid gap-3', 'grid-cols-1' => count($participantesView) === 1, 'grid-cols-2' => count($participantesView) !== 1])>
                                            @foreach ($participantesView as $indice => $nomeParticipante)
                                                <div>
                                                    <label class="mb-1 block text-xs font-medium text-slate-500">Participante {{ $indice + 1 }}</label>
                                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900">
                                                        {{ $nomeParticipante }}
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-400">—</div>
                                    @endif
                                </div>

                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Observações</label>
                                    <div class="rich-text-editor__content min-h-[4rem] rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900">
                                        @if ($ordemView['observacoes'])
                                            {!! $ordemView['observacoes'] !!}
                                        @else
                                            —
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if (! empty($ordemView['pausas']))
                            <div class="mt-6 space-y-3 border-t border-slate-100 pt-6">
                                <h3 class="text-sm font-semibold text-slate-900">Histórico de pausas</h3>
                                <div class="space-y-2">
                                    @foreach (array_reverse($ordemView['pausas']) as $pausa)
                                        <div class="rounded-lg border border-amber-100 bg-amber-50/60 px-3 py-2.5">
                                            <p class="text-xs font-medium text-amber-800">
                                                {{ \Illuminate\Support\Carbon::parse($pausa['em'])->format('d/m/Y H:i') }}
                                            </p>
                                            <p class="mt-0.5 text-sm text-amber-900">{{ $pausa['motivo'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="flex min-h-0 w-full flex-col border-t border-slate-100 bg-slate-50/50 lg:w-1/2 lg:border-t-0">
                        <div class="shrink-0 border-b border-slate-100 px-6 py-4">
                            <h3 class="text-sm font-semibold text-slate-900">Comentários</h3>
                            <p class="text-xs text-slate-500">{{ count($ordemView['comentarios'] ?? []) }} {{ count($ordemView['comentarios'] ?? []) === 1 ? 'comentário' : 'comentários' }}</p>
                        </div>

                        <div class="min-h-0 flex-1 space-y-3 overflow-y-auto px-6 py-4">
                            @forelse (array_reverse($ordemView['comentarios'] ?? []) as $comentario)
                                <article wire:key="comentario-ordem-{{ $comentario['id'] }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <div class="mb-2 flex items-start justify-between gap-3">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-700">
                                                {{ strtoupper(substr($comentario['autor'], 0, 1).substr(strstr($comentario['autor'], ' ') ?: '', 1, 1)) }}
                                            </span>
                                            <div>
                                                <p class="text-sm font-medium text-slate-900">{{ $comentario['autor'] }}</p>
                                                <p class="text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($comentario['criado_em'])->format('d/m/Y H:i') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-sm whitespace-pre-wrap text-slate-700">{{ $comentario['texto'] }}</p>
                                </article>
                            @empty
                                <div class="flex h-full min-h-[12rem] flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white px-6 py-8 text-center">
                                    <x-icon name="chat-bubble-left-right" class="mb-3 h-8 w-8 text-slate-300" />
                                    <p class="text-sm font-medium text-slate-600">Nenhum comentário ainda</p>
                                    <p class="mt-1 text-xs text-slate-500">Seja o primeiro a registrar uma observação sobre esta ordem.</p>
                                </div>
                            @endforelse
                        </div>

                        <div class="shrink-0 border-t border-slate-100 bg-white px-6 py-4">
                            <form wire:submit="adicionarComentario" class="space-y-3">
                                <x-textarea
                                    wire:model="novoComentario"
                                    label="Novo comentário"
                                    placeholder="Registre um comentário durante a execução..."
                                    rows="3"
                                    :disabled="$finalizada"
                                />
                                <div class="flex justify-end">
                                    <x-button primary type="submit" icon="paper-airplane" label="Enviar comentário" :disabled="$finalizada" />
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="flex shrink-0 justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                    <x-button flat label="Fechar" wire:click="closeVisualizar" />
                    <x-dropdown position="top-end" width="sm" wire:key="ordem-menu-visualizar-{{ $ordemView['id'] }}">
                        <x-slot name="trigger">
                            <button
                                type="button"
                                title="Mais opções"
                                class="inline-flex h-10 items-center justify-center rounded-lg px-3 text-slate-600 ring-1 ring-slate-200 hover:bg-slate-100 hover:text-brand-600"
                            >
                                <x-icon name="ellipsis-vertical" class="h-5 w-5" />
                            </button>
                        </x-slot>

                        <button
                            type="button"
                            wire:click.stop="gerarPdf({{ $ordemView['id'] }})"
                            class="flex w-full cursor-pointer items-center rounded-md px-4 py-2 text-sm text-secondary-600 transition-colors duration-150 hover:bg-secondary-100 hover:text-secondary-900"
                        >
                            <x-icon name="document-arrow-down" class="mr-2 h-5 w-5" />
                            Gerar PDF
                        </button>
                    </x-dropdown>
                    <x-button primary icon="pencil" label="Editar ordem" wire:click="edit({{ $ordemView['id'] }})" />
                </div>
            </div>
        </div>
    @endif

    @if ($finalizandoOrdemId)
        <div
            wire:key="modal-finalizar-ordem"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <button
                type="button"
                class="absolute inset-0 bg-slate-900/50 backdrop-blur-[1px]"
                wire:click="cancelarFinalizacao"
                aria-label="Fechar"
            ></button>

            <div class="relative flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="text-lg font-semibold text-slate-900">Finalizar ordem de serviço</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        OS #{{ str_pad((string) $finalizandoOrdemId, 3, '0', STR_PAD_LEFT) }} — preencha o registro da execução antes de concluir.
                    </p>
                </div>

                <form wire:submit="confirmarFinalizacao" class="flex min-h-0 flex-1 flex-col">
                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-6 py-5">
                        <div>
                            <x-input
                                wire:model="execTempoTotal"
                                label="Tempo total"
                                placeholder="00:00:00"
                                class="font-mono"
                            />
                            <p class="mt-1 text-xs text-slate-500">Formato HH:MM:SS — ajuste se necessário.</p>
                        </div>

                        <x-ordens-servico.rich-text-editor
                            wire-model="execDescricaoServicos"
                            label="Descrição dos Serviços"
                            placeholder="Descreva os serviços realizados..."
                            editor-key="finalizar-descricao-{{ $finalizandoOrdemId }}"
                            min-height="min-h-[7rem]"
                        />

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Participantes</label>
                            <div class="grid grid-cols-2 gap-3">
                                <x-input wire:model="execParticipante1" label="Participante 1" placeholder="Nome do participante" />
                                <x-input wire:model="execParticipante2" label="Participante 2" placeholder="Nome do participante" />
                                <x-input wire:model="execParticipante3" label="Participante 3" placeholder="Nome do participante" />
                                <x-input wire:model="execParticipante4" label="Participante 4" placeholder="Nome do participante" />
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Técnicos disponíveis: {{ collect($tecnicosDisponiveis)->pluck('nome')->implode(', ') }}</p>
                        </div>

                        <x-ordens-servico.rich-text-editor
                            wire-model="execObservacoes"
                            label="Observações"
                            placeholder="Anotações gerais sobre a execução..."
                            editor-key="finalizar-observacoes-{{ $finalizandoOrdemId }}"
                            min-height="min-h-[5rem]"
                        />
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                        <x-button flat label="Cancelar" wire:click="cancelarFinalizacao" type="button" />
                        <button
                            type="submit"
                            class="inline-flex h-10 items-center gap-2 rounded-lg bg-red-500 px-4 text-sm font-medium text-white shadow-sm hover:bg-red-600"
                        >
                            <x-icon name="stop" class="h-4 w-4" />
                            Finalizar ordem
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($pausandoOrdemId)
        <div
            wire:key="modal-pausa-ordem"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <button
                type="button"
                class="absolute inset-0 bg-slate-900/50 backdrop-blur-[1px]"
                wire:click="cancelarPausa"
                aria-label="Fechar"
            ></button>

            <div class="relative w-full max-w-lg rounded-xl border border-slate-200 bg-white p-6 shadow-2xl">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-slate-900">Motivo da pausa</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Informe o motivo da interrupção. A data e hora serão registradas automaticamente.
                    </p>
                </div>

                <form wire:submit="confirmarPausa" class="space-y-4">
                    <x-textarea
                        wire:model="motivoPausa"
                        label="Motivo"
                        placeholder="Ex: Aguardando liberação do cliente, falta de material..."
                        rows="4"
                    />

                    <div class="flex justify-end gap-3">
                        <x-button flat label="Cancelar" wire:click="cancelarPausa" type="button" />
                        <button
                            type="submit"
                            class="inline-flex h-10 items-center gap-2 rounded-lg bg-amber-500 px-4 text-sm font-medium text-white shadow-sm hover:bg-amber-600"
                        >
                            <x-icon name="pause" class="h-4 w-4" />
                            Confirmar pausa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
