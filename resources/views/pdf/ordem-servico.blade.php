<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Ordem de Serviço {{ $numeroOrdem }}</title>
    <style>
        @page { margin: 14mm 12mm 16mm 12mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; margin: 0; padding: 0; color: #1f2937; font-size: 9.5pt; line-height: 1.5; }
        table { border-collapse: collapse; }
        .muted { color: #6b7280; }
        .label { font-size: 7pt; font-weight: bold; color: #6b7280; text-transform: uppercase; letter-spacing: 0.6pt; margin-bottom: 3pt; }
        .value { font-size: 9.5pt; color: #111827; }
        .value-strong { font-size: 9.5pt; font-weight: bold; color: #111827; }
        .section-head { background-color: #f3f4f6; border-left: 3pt solid #004200; padding: 7pt 10pt; font-size: 7.5pt; font-weight: bold; color: #374151; text-transform: uppercase; letter-spacing: 0.8pt; }
        .section-body { border: 1pt solid #e5e7eb; border-top: none; padding: 10pt 12pt; background-color: #ffffff; }
        .field-cell { padding: 8pt 10pt; border: 1pt solid #e5e7eb; vertical-align: top; }
        .content-box { font-size: 9.5pt; color: #374151; line-height: 1.6; }
        .sig-box { border: 1pt solid #d1d5db; background-color: #fafafa; padding: 10pt 12pt 8pt; }
        .sig-line { border-top: 1pt solid #374151; margin-top: 36pt; padding-top: 5pt; text-align: center; font-size: 7pt; color: #6b7280; text-transform: uppercase; letter-spacing: 0.4pt; }
    </style>
</head>
@php
    $verde = '#004200';
    $verdeEscuro = '#003300';
    $nomeEmpresa = ! empty($empresa['razao_social']) ? $empresa['razao_social'] : ($empresa['nome_empresa'] ?? '');
    $nomeFantasia = ! empty($empresa['nome_empresa']) && $nomeEmpresa !== $empresa['nome_empresa'] ? $empresa['nome_empresa'] : '';
    $linhaLocal = trim(collect([
        $empresa['cidade'] ?? '',
        ! empty($empresa['estado']) ? $empresa['estado'] : '',
        ! empty($empresa['cep']) ? 'CEP '.$empresa['cep'] : '',
    ])->filter()->implode(' · '));
    $emitidoEm = now()->format('d/m/Y \à\s H:i');
@endphp
<body>

    {{-- Faixa superior --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 0;">
        <tr>
            <td style="background-color: {{ $verde }}; padding: 11pt 14pt;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="middle">
                            <div style="font-size: 15pt; font-weight: bold; color: #ffffff; letter-spacing: 1pt; text-transform: uppercase;">Ordem de Serviço</div>
                            <div style="font-size: 7.5pt; color: #c8e6c8; margin-top: 2pt; letter-spacing: 0.3pt;">Documento oficial de prestação de serviços</div>
                        </td>
                        <td width="130" valign="middle" align="right">
                            <div style="font-size: 7pt; color: #c8e6c8; text-transform: uppercase; letter-spacing: 0.5pt; margin-bottom: 2pt;">Número</div>
                            <div style="font-size: 20pt; font-weight: bold; color: #ffffff; line-height: 1;">{{ $numeroOrdem }}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Meta + empresa --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="border: 1pt solid #e5e7eb; border-top: none; margin-bottom: 14pt;">
        <tr>
            <td style="padding: 12pt 14pt; border-right: 1pt solid #e5e7eb; vertical-align: top;">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="64" valign="top" style="padding-right: 12pt;">
                            @if (! empty($empresa['logo']))
                                <img src="{{ $empresa['logo'] }}" width="58" height="58" alt="Logo" style="display: block; border: 1pt solid #e5e7eb;">
                            @else
                                <table width="58" height="58" cellpadding="0" cellspacing="0" style="border: 1pt solid #d1d5db; background-color: #f9fafb;">
                                    <tr>
                                        <td align="center" valign="middle" style="font-size: 7pt; font-weight: bold; color: {{ $verde }};">LOGO</td>
                                    </tr>
                                </table>
                            @endif
                        </td>
                        <td valign="top">
                            @if ($nomeEmpresa !== '')
                                <div style="font-size: 10.5pt; font-weight: bold; color: #111827; line-height: 1.3; margin-bottom: 3pt;">{{ $nomeEmpresa }}</div>
                            @endif
                            @if ($nomeFantasia !== '')
                                <div style="font-size: 8pt; color: #6b7280; margin-bottom: 5pt;">{{ $nomeFantasia }}</div>
                            @endif
                            <table cellpadding="0" cellspacing="0" style="font-size: 8pt; color: #6b7280; line-height: 1.65;">
                                @if (! empty($empresa['cnpj']))
                                    <tr><td><span style="color: #374151; font-weight: bold;">CNPJ</span>&nbsp; {{ $empresa['cnpj'] }}</td></tr>
                                @endif
                                @if (! empty($empresa['endereco']))
                                    <tr><td>{{ $empresa['endereco'] }}</td></tr>
                                @endif
                                @if ($linhaLocal !== '')
                                    <tr><td>{{ $linhaLocal }}</td></tr>
                                @endif
                                @if (! empty($empresa['telefone']))
                                    <tr><td><span style="color: #374151; font-weight: bold;">Tel.</span>&nbsp; {{ $empresa['telefone'] }}</td></tr>
                                @endif
                                @if (! empty($empresa['email']))
                                    <tr><td>{{ $empresa['email'] }}</td></tr>
                                @endif
                                @if (! empty($empresa['site']))
                                    <tr><td>{{ $empresa['site'] }}</td></tr>
                                @endif
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
            <td width="38%" valign="top" style="padding: 0; background-color: #f9fafb;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding: 8pt 12pt; border-bottom: 1pt solid #e5e7eb;">
                            <div class="label">Data do atendimento</div>
                            <div class="value-strong">{{ $dataDocumento }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8pt 12pt; border-bottom: 1pt solid #e5e7eb;">
                            <div class="label">Técnico responsável</div>
                            <div class="value-strong">{{ $tecnico }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8pt 12pt;">
                            <div class="label">Tipo de chamado</div>
                            <table cellpadding="0" cellspacing="0" style="margin-top: 4pt;">
                                <tr>
                                    <td style="background-color: {{ $verde }}; color: #ffffff; font-size: 7.5pt; font-weight: bold; padding: 3pt 8pt; text-transform: uppercase; letter-spacing: 0.4pt;">{{ $tipo }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Cliente --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12pt;">
        <tr><td class="section-head">01 · Dados do cliente</td></tr>
        <tr>
            <td class="section-body" style="padding: 0;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="50%" class="field-cell" style="border-top: none; border-left: none;">
                            <div class="label">Nome / Razão social</div>
                            <div class="value-strong">{{ $cliente['nome'] ?? '—' }}</div>
                        </td>
                        <td width="50%" class="field-cell" style="border-top: none; border-right: none;">
                            <div class="label">CNPJ / CPF</div>
                            <div class="value">{{ $cliente['documento'] ?? '—' }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="field-cell" style="border-left: none;">
                            <div class="label">Telefone</div>
                            <div class="value">{{ $cliente['telefone'] ?? '—' }}</div>
                        </td>
                        <td class="field-cell" style="border-right: none;">
                            <div class="label">E-mail</div>
                            <div class="value">{{ $cliente['email'] ?? '—' }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="field-cell" style="border-left: none; border-right: none; border-bottom: none;">
                            <div class="label">Endereço completo</div>
                            <div class="value">{{ $clienteEndereco ?: '—' }}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Chamado --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12pt;">
        <tr><td class="section-head">02 · Descrição do chamado</td></tr>
        <tr>
            <td class="section-body">
                @if (! empty($tituloChamado))
                    <div style="font-size: 10.5pt; font-weight: bold; color: #111827; margin-bottom: 8pt; padding-bottom: 8pt; border-bottom: 1pt solid #f3f4f6;">
                        {{ $tituloChamado }}
                    </div>
                @endif
                <div class="content-box">
                    @if (! empty($ordem['descricao']))
                        {!! $ordem['descricao'] !!}
                    @else
                        <span class="muted">Nenhuma descrição informada.</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Serviços --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12pt;">
        <tr><td class="section-head">03 · Serviços executados</td></tr>
        <tr>
            <td class="section-body">
                <div class="content-box" style="min-height: 52pt;">
                    @if (! empty($ordem['descricao_servicos']))
                        {!! $ordem['descricao_servicos'] !!}
                    @else
                        <span class="muted">Nenhum serviço descrito.</span>
                    @endif
                </div>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 10pt;">
                    <tr>
                        <td align="right" style="padding: 8pt 12pt; background-color: #f0f7f0; border: 1pt solid #c5dcc5;">
                            <span class="label" style="display: inline; margin: 0;">Tempo total do serviço</span>
                            <span style="font-size: 12pt; font-weight: bold; color: {{ $verde }}; margin-left: 10pt;">{{ $tempoOrdem }}</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Participantes --}}
    @if (count($participantes) > 0)
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12pt;">
            <tr><td class="section-head">04 · Participantes e assinaturas</td></tr>
            <tr>
                <td class="section-body">
                    @foreach (array_chunk($participantes, 2) as $linha)
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: {{ $loop->last ? '0' : '10pt' }};">
                            <tr>
                                @foreach ($linha as $participante)
                                    <td width="50%" valign="top" style="padding: {{ $loop->first ? '0 6pt 0 0' : '0 0 0 6pt' }};">
                                        <div class="sig-box">
                                            <div style="font-size: 9pt; font-weight: bold; color: #374151; margin-bottom: 2pt;">{{ $participante }}</div>
                                            <div class="sig-line">Assinatura</div>
                                        </div>
                                    </td>
                                @endforeach
                                @if (count($linha) === 1)
                                    <td width="50%"></td>
                                @endif
                            </tr>
                        </table>
                    @endforeach
                </td>
            </tr>
        </table>
    @endif

    {{-- Observações --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12pt;">
        <tr><td class="section-head">{{ count($participantes) > 0 ? '05' : '04' }} · Observações</td></tr>
        <tr>
            <td class="section-body">
                <div class="content-box" style="min-height: 36pt;">
                    @if (! empty($ordem['observacoes']))
                        {!! $ordem['observacoes'] !!}
                    @else
                        <span class="muted">Sem observações adicionais.</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Termo --}}
    @php $numTermo = count($participantes) > 0 ? '06' : '05'; @endphp
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 14pt;">
        <tr><td class="section-head">{{ $numTermo }} · Termo de concordância</td></tr>
        <tr>
            <td class="section-body">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="55%" valign="top" style="padding-right: 14pt;">
                            <div style="font-size: 8.5pt; color: #4b5563; line-height: 1.65; font-style: italic; padding: 8pt 10pt; background-color: #f9fafb; border-left: 2pt solid {{ $verde }};">
                                Declaro que o serviço descrito neste documento foi executado conforme combinado e autorizo a cobrança dos valores aqui contidos, ressalvadas coberturas por contrato ou garantia vigente.
                            </div>
                        </td>
                        <td width="45%" valign="bottom">
                            <div class="sig-box" style="background-color: #ffffff;">
                                <div style="height: 8pt;"></div>
                                <div class="sig-line">Assinatura do responsável / cliente</div>
                                <div style="text-align: center; font-size: 7pt; color: #9ca3af; margin-top: 4pt;">Nome legível · CPF/CNPJ</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Rodapé --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="border-top: 1pt solid #e5e7eb; padding-top: 8pt;">
        <tr>
            <td style="font-size: 7pt; color: #9ca3af; line-height: 1.5;">
                @if ($nomeEmpresa !== '')
                    <strong style="color: #6b7280;">{{ $nomeEmpresa }}</strong><br>
                @endif
                Documento emitido em {{ $emitidoEm }}
            </td>
            <td align="right" valign="bottom" style="font-size: 7pt; color: #9ca3af;">
                OS-{{ $numeroOrdem }} · Página 1 de 1
            </td>
        </tr>
    </table>

</body>
</html>
