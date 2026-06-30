<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Ordem de Serviço {{ $numeroOrdem }}</title>
    <style>
        @page { margin: 16mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; margin: 0; padding: 0; color: #333333; font-size: 9.5pt; line-height: 1.55; }
        table { border-collapse: collapse; }
    </style>
</head>
@php
    $verde = '#005300';
    $texto = '#333333';
    $suave = '#666666';
    $borda = '#dddddd';
    $nomeEmpresa = ! empty($empresa['razao_social']) ? $empresa['razao_social'] : ($empresa['nome_empresa'] ?? '');
    $linhaLocal = trim(collect([
        $empresa['cidade'] ?? '',
        ! empty($empresa['estado']) ? $empresa['estado'] : '',
        ! empty($empresa['cep']) ? 'CEP '.$empresa['cep'] : '',
    ])->filter()->implode(' — '));
@endphp
<body>

    {{-- Cabeçalho --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 16pt; border-bottom: 1.5pt solid {{ $verde }}; padding-bottom: 12pt;">
        <tr>
            <td valign="top" style="padding-right: 12pt;">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="56" valign="top" style="padding-right: 10pt;">
                            @if (! empty($empresa['logo']))
                                <img src="{{ $empresa['logo'] }}" width="52" height="52" alt="Logo" style="display: block;">
                            @else
                                <table width="52" height="52" cellpadding="0" cellspacing="0" style="border: 0.5pt solid {{ $borda }};">
                                    <tr>
                                        <td align="center" valign="middle" style="font-size: 7pt; color: {{ $suave }};">Logo</td>
                                    </tr>
                                </table>
                            @endif
                        </td>
                        <td valign="top">
                            @if ($nomeEmpresa !== '')
                                <div style="font-size: 10pt; font-weight: bold; color: {{ $texto }}; margin-bottom: 4pt;">{{ $nomeEmpresa }}</div>
                            @endif
                            <div style="font-size: 8pt; color: {{ $suave }}; line-height: 1.6;">
                                @if (! empty($empresa['cnpj']))
                                    CNPJ {{ $empresa['cnpj'] }}<br>
                                @endif
                                @if (! empty($empresa['endereco']))
                                    {{ $empresa['endereco'] }}<br>
                                @endif
                                @if ($linhaLocal !== '')
                                    {{ $linhaLocal }}<br>
                                @endif
                                @if (! empty($empresa['telefone']))
                                    {{ $empresa['telefone'] }}
                                    @if (! empty($empresa['email']))
                                        · {{ $empresa['email'] }}
                                    @endif
                                    <br>
                                @elseif (! empty($empresa['email']))
                                    {{ $empresa['email'] }}<br>
                                @endif
                                @if (! empty($empresa['site']))
                                    {{ $empresa['site'] }}
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td width="175" valign="top" align="right">
                <div style="font-size: 11pt; font-weight: bold; color: {{ $verde }}; margin-bottom: 8pt;">Ordem de Serviço</div>
                <table width="100%" cellpadding="0" cellspacing="0" style="font-size: 8.5pt; color: {{ $texto }};">
                    <tr>
                        <td style="padding: 2pt 0; color: {{ $suave }};">Nº</td>
                        <td align="right" style="padding: 2pt 0; font-weight: bold;">{{ $numeroOrdem }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 2pt 0; color: {{ $suave }};">Data</td>
                        <td align="right" style="padding: 2pt 0;">{{ $dataDocumento }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 2pt 0; color: {{ $suave }};">Técnico</td>
                        <td align="right" style="padding: 2pt 0;">{{ $tecnico }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 2pt 0; color: {{ $suave }};">Tipo</td>
                        <td align="right" style="padding: 2pt 0;">{{ $tipo }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Cliente --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 14pt;">
        <tr>
            <td style="font-size: 8.5pt; font-weight: bold; color: {{ $verde }}; padding-bottom: 6pt; border-bottom: 0.5pt solid {{ $borda }};">
                Dados do cliente
            </td>
        </tr>
        <tr>
            <td style="padding-top: 8pt;">
                <table width="100%" cellpadding="0" cellspacing="0" style="font-size: 9pt;">
                    <tr>
                        <td width="50%" valign="top" style="padding: 0 10pt 6pt 0;">
                            <span style="color: {{ $suave }};">Nome</span><br>
                            {{ $cliente['nome'] ?? '—' }}
                        </td>
                        <td width="50%" valign="top" style="padding: 0 0 6pt 10pt;">
                            <span style="color: {{ $suave }};">CNPJ / CPF</span><br>
                            {{ $cliente['documento'] ?? '—' }}
                        </td>
                    </tr>
                    <tr>
                        <td valign="top" style="padding: 6pt 10pt 6pt 0;">
                            <span style="color: {{ $suave }};">Telefone</span><br>
                            {{ $cliente['telefone'] ?? '—' }}
                        </td>
                        <td valign="top" style="padding: 6pt 0 6pt 10pt;">
                            <span style="color: {{ $suave }};">E-mail</span><br>
                            {{ $cliente['email'] ?? '—' }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" valign="top" style="padding: 6pt 0 0;">
                            <span style="color: {{ $suave }};">Endereço</span><br>
                            {{ $clienteEndereco ?: '—' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Chamado --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 14pt;">
        <tr>
            <td style="font-size: 8.5pt; font-weight: bold; color: {{ $verde }}; padding-bottom: 6pt; border-bottom: 0.5pt solid {{ $borda }};">
                Descrição do chamado
            </td>
        </tr>
        <tr>
            <td style="padding-top: 8pt; font-size: 9pt; color: {{ $texto }};">
                @if (! empty($tituloChamado))
                    <div style="font-weight: bold; margin-bottom: 6pt;">{{ $tituloChamado }}</div>
                @endif
                @if (! empty($ordem['descricao']))
                    {!! $ordem['descricao'] !!}
                @else
                    <span style="color: {{ $suave }};">—</span>
                @endif
            </td>
        </tr>
    </table>

    {{-- Serviços --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 14pt;">
        <tr>
            <td style="font-size: 8.5pt; font-weight: bold; color: {{ $verde }}; padding-bottom: 6pt; border-bottom: 0.5pt solid {{ $borda }};">
                Serviços executados
            </td>
        </tr>
        <tr>
            <td style="padding-top: 8pt; font-size: 9pt; color: {{ $texto }};">
                @if (! empty($ordem['descricao_servicos']))
                    {!! $ordem['descricao_servicos'] !!}
                @else
                    <span style="color: {{ $suave }};">—</span>
                @endif
            </td>
        </tr>
        <tr>
            <td align="right" style="padding-top: 10pt; font-size: 8.5pt; color: {{ $suave }};">
                Tempo do serviço: <span style="font-weight: bold; color: {{ $texto }};">{{ $tempoOrdem }}</span>
            </td>
        </tr>
    </table>

    {{-- Participantes --}}
    @if (count($participantes) > 0)
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 14pt;">
            <tr>
                <td style="font-size: 8.5pt; font-weight: bold; color: {{ $verde }}; padding-bottom: 6pt; border-bottom: 0.5pt solid {{ $borda }};">
                    Participantes
                </td>
            </tr>
            <tr>
                <td style="padding-top: 10pt;">
                    @foreach (array_chunk($participantes, 2) as $linha)
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: {{ $loop->last ? '0' : '12pt' }};">
                            <tr>
                                @foreach ($linha as $participante)
                                    <td width="50%" valign="bottom" style="padding: {{ $loop->first ? '0 12pt 0 0' : '0 0 0 12pt' }};">
                                        <div style="height: 32pt;"></div>
                                        <div style="border-top: 0.5pt solid {{ $texto }}; padding-top: 4pt; text-align: center; font-size: 7.5pt; color: {{ $suave }};">
                                            {{ $participante }}
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
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 14pt;">
        <tr>
            <td style="font-size: 8.5pt; font-weight: bold; color: {{ $verde }}; padding-bottom: 6pt; border-bottom: 0.5pt solid {{ $borda }};">
                Observações
            </td>
        </tr>
        <tr>
            <td style="padding-top: 8pt; font-size: 9pt; color: {{ $texto }};">
                @if (! empty($ordem['observacoes']))
                    {!! $ordem['observacoes'] !!}
                @else
                    <span style="color: {{ $suave }};">—</span>
                @endif
            </td>
        </tr>
    </table>

    {{-- Termo --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 16pt;">
        <tr>
            <td style="font-size: 8.5pt; font-weight: bold; color: {{ $verde }}; padding-bottom: 6pt; border-bottom: 0.5pt solid {{ $borda }};">
                Termo de concordância
            </td>
        </tr>
        <tr>
            <td style="padding-top: 8pt;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="58%" valign="top" style="padding-right: 16pt; font-size: 8.5pt; color: {{ $suave }}; line-height: 1.6;">
                            Confirmo que o serviço foi executado conforme combinado e autorizo a cobrança dos valores aqui contidos, ressalvadas coberturas por contrato ou garantia.
                        </td>
                        <td width="42%" valign="bottom">
                            <div style="height: 32pt;"></div>
                            <div style="border-top: 0.5pt solid {{ $texto }}; padding-top: 4pt; text-align: center; font-size: 7.5pt; color: {{ $suave }};">
                                Assinatura do responsável
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Rodapé --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="border-top: 0.5pt solid {{ $borda }}; padding-top: 6pt;">
        <tr>
            <td style="font-size: 7pt; color: #999999;">
                @if ($nomeEmpresa !== '')
                    {{ $nomeEmpresa }} ·
                @endif
                Emitido em {{ now()->format('d/m/Y H:i') }}
            </td>
            <td align="right" style="font-size: 7pt; color: #999999;">
                OS-{{ $numeroOrdem }}
            </td>
        </tr>
    </table>

</body>
</html>
