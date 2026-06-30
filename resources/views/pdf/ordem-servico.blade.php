<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Ordem de Serviço {{ $numeroOrdem }}</title>
</head>
@php
    $corPrincipal = '#005300';
    $corPrincipalClara = '#e8f3e8';
    $corTexto = '#1a1a1a';
    $corTextoSuave = '#4a5568';
    $corBorda = '#c5d9c5';
    $raioSecao = '6px';
    $raioBadge = '4px';
    $estiloSecao = "border: 1px solid {$corBorda}; border-radius: {$raioSecao}; overflow: hidden; margin-bottom: 12px;";
    $estiloTituloSecao = "background-color: {$corPrincipal}; color: #ffffff; font-size: 9px; font-weight: bold; padding: 6px 12px; text-transform: uppercase; letter-spacing: 0.6px;";
    $estiloConteudoSecao = "padding: 10px 12px; color: {$corTexto}; font-size: 10px; background-color: #fafcfa;";

    $nomeEmpresa = ! empty($empresa['razao_social']) ? $empresa['razao_social'] : ($empresa['nome_empresa'] ?? '');
    $linhaLocal = trim(collect([
        $empresa['cidade'] ?? '',
        ! empty($empresa['estado']) ? $empresa['estado'] : '',
        ! empty($empresa['cep']) ? 'CEP '.$empresa['cep'] : '',
    ])->filter()->implode(' — '));
@endphp
<body style="font-family: DejaVu Sans, sans-serif; font-size: 10px; color: {{ $corTexto }}; margin: 0; padding: 20px 24px; line-height: 1.45;">

    {{-- Cabeçalho --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 14px;">
        <tr>
            {{-- Logo + empresa --}}
            <td valign="top" style="padding-right: 14px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="72" valign="top" style="padding-right: 12px;">
                            @if (! empty($empresa['logo']))
                                <img src="{{ $empresa['logo'] }}" width="68" height="68" alt="Logo" style="display: block; border: 1px solid {{ $corBorda }}; border-radius: {{ $raioSecao }};">
                            @else
                                <table width="68" height="68" cellpadding="0" cellspacing="0" style="border: 1px solid {{ $corPrincipal }}; background-color: {{ $corPrincipalClara }}; border-radius: {{ $raioSecao }};">
                                    <tr>
                                        <td align="center" valign="middle" style="font-size: 8px; font-weight: bold; color: {{ $corPrincipal }}; letter-spacing: 0.5px;">LOGO</td>
                                    </tr>
                                </table>
                            @endif
                        </td>
                        <td valign="top">
                            @if ($nomeEmpresa !== '')
                                <div style="font-size: 13px; font-weight: bold; color: {{ $corTexto }}; line-height: 1.25; margin-bottom: 6px; text-transform: uppercase;">
                                    {{ $nomeEmpresa }}
                                </div>
                            @endif
                            <table cellpadding="0" cellspacing="0" style="font-size: 9px; color: {{ $corTextoSuave }}; line-height: 1.65;">
                                @if (! empty($empresa['cnpj']))
                                    <tr>
                                        <td style="padding-bottom: 1px;"><span style="color: {{ $corTexto }}; font-weight: bold;">CNPJ:</span> {{ $empresa['cnpj'] }}</td>
                                    </tr>
                                @endif
                                @if (! empty($empresa['endereco']))
                                    <tr>
                                        <td style="padding-bottom: 1px;">{{ $empresa['endereco'] }}</td>
                                    </tr>
                                @endif
                                @if ($linhaLocal !== '')
                                    <tr>
                                        <td style="padding-bottom: 1px;">{{ $linhaLocal }}</td>
                                    </tr>
                                @endif
                                @if (! empty($empresa['telefone']) || ! empty($empresa['email']))
                                    <tr>
                                        <td style="padding-bottom: 1px;">
                                            @if (! empty($empresa['telefone']))
                                                <span style="color: {{ $corTexto }}; font-weight: bold;">Tel:</span> {{ $empresa['telefone'] }}
                                            @endif
                                            @if (! empty($empresa['telefone']) && ! empty($empresa['email']))
                                                &nbsp;&nbsp;·&nbsp;&nbsp;
                                            @endif
                                            @if (! empty($empresa['email']))
                                                {{ $empresa['email'] }}
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                                @if (! empty($empresa['site']))
                                    <tr>
                                        <td>{{ $empresa['site'] }}</td>
                                    </tr>
                                @endif
                            </table>
                        </td>
                    </tr>
                </table>
            </td>

            {{-- Bloco do documento --}}
            <td width="200" valign="top" align="right">
                <table width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid {{ $corPrincipal }}; border-radius: {{ $raioSecao }}; overflow: hidden;">
                    <tr>
                        <td align="center" style="background-color: {{ $corPrincipal }}; color: #ffffff; font-size: 12px; font-weight: bold; padding: 8px 10px; letter-spacing: 0.8px; text-transform: uppercase;">
                            Ordem de Serviço
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 10px; background-color: #ffffff;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="font-size: 9px; color: {{ $corTexto }};">
                                <tr>
                                    <td width="42%" style="font-weight: bold; padding: 2px 0; color: {{ $corTextoSuave }};">Nº da Ordem</td>
                                    <td align="right" style="font-weight: bold; padding: 2px 0;">{{ $numeroOrdem }}</td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="border-top: 1px solid {{ $corBorda }}; padding: 0; height: 1px; line-height: 1px; font-size: 1px;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; padding: 3px 0 2px; color: {{ $corTextoSuave }};">Data</td>
                                    <td align="right" style="padding: 3px 0 2px;">{{ $dataDocumento }}</td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="border-top: 1px solid {{ $corBorda }}; padding: 0; height: 1px; line-height: 1px; font-size: 1px;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; padding: 3px 0 2px; color: {{ $corTextoSuave }};">Técnico</td>
                                    <td align="right" style="padding: 3px 0 2px;">{{ $tecnico }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 14px;">
        <tr>
            <td style="border-bottom: 3px solid {{ $corPrincipal }}; font-size: 1px; line-height: 1px;">&nbsp;</td>
        </tr>
    </table>

    {{-- Dados do cliente --}}
    <div style="{{ $estiloSecao }}">
        <div style="{{ $estiloTituloSecao }}">Dados do cliente</div>
        <div style="{{ $estiloConteudoSecao }}">
            <table width="100%" cellpadding="0" cellspacing="0" style="font-size: 10px;">
                <tr>
                    <td width="50%" valign="top" style="padding: 4px 8px 4px 0; border-bottom: 1px solid {{ $corBorda }};">
                        <div style="font-size: 8px; font-weight: bold; color: {{ $corTextoSuave }}; text-transform: uppercase; margin-bottom: 2px;">Nome</div>
                        {{ $cliente['nome'] ?? '—' }}
                    </td>
                    <td width="50%" valign="top" style="padding: 4px 0 4px 8px; border-bottom: 1px solid {{ $corBorda }};">
                        <div style="font-size: 8px; font-weight: bold; color: {{ $corTextoSuave }}; text-transform: uppercase; margin-bottom: 2px;">CNPJ</div>
                        {{ $cliente['documento'] ?? '—' }}
                    </td>
                </tr>
                <tr>
                    <td width="50%" valign="top" style="padding: 6px 8px 4px 0; border-bottom: 1px solid {{ $corBorda }};">
                        <div style="font-size: 8px; font-weight: bold; color: {{ $corTextoSuave }}; text-transform: uppercase; margin-bottom: 2px;">Telefone</div>
                        {{ $cliente['telefone'] ?? '—' }}
                    </td>
                    <td width="50%" valign="top" style="padding: 6px 0 4px 8px; border-bottom: 1px solid {{ $corBorda }};">
                        <div style="font-size: 8px; font-weight: bold; color: {{ $corTextoSuave }}; text-transform: uppercase; margin-bottom: 2px;">E-mail</div>
                        {{ $cliente['email'] ?? '—' }}
                    </td>
                </tr>
                <tr>
                    <td colspan="2" valign="top" style="padding: 6px 0 2px;">
                        <div style="font-size: 8px; font-weight: bold; color: {{ $corTextoSuave }}; text-transform: uppercase; margin-bottom: 2px;">Endereço</div>
                        {{ $clienteEndereco }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Descrição do chamado --}}
    <div style="{{ $estiloSecao }}">
        <div style="{{ $estiloTituloSecao }}">Descrição do chamado</div>
        <div style="{{ $estiloConteudoSecao }}">
            <table cellpadding="0" cellspacing="0" style="margin-bottom: 8px;">
                <tr>
                    <td style="background-color: {{ $corPrincipalClara }}; border: 1px solid {{ $corPrincipal }}; border-radius: {{ $raioBadge }}; font-size: 8px; font-weight: bold; padding: 3px 10px; color: {{ $corPrincipal }}; text-transform: uppercase; letter-spacing: 0.4px;">
                        {{ $tipo }}
                    </td>
                </tr>
            </table>
            @if (! empty($tituloChamado))
                <div style="font-weight: bold; font-size: 11px; margin-bottom: 6px; color: {{ $corTexto }};">{{ $tituloChamado }}</div>
            @endif
            <div style="font-size: 10px; color: {{ $corTexto }}; line-height: 1.55;">
                @if (! empty($ordem['descricao']))
                    {!! $ordem['descricao'] !!}
                @else
                    —
                @endif
            </div>
        </div>
    </div>

    {{-- Descrição dos serviços --}}
    <div style="{{ $estiloSecao }}">
        <div style="{{ $estiloTituloSecao }}">Descrição dos serviços</div>
        <div style="{{ $estiloConteudoSecao }}">
            <div style="min-height: 48px; padding-bottom: 8px; line-height: 1.55;">
                @if (! empty($ordem['descricao_servicos']))
                    {!! $ordem['descricao_servicos'] !!}
                @else
                    —
                @endif
            </div>
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="border-top: 1px solid {{ $corBorda }}; padding-top: 8px;" align="right">
                        <span style="font-size: 8px; font-weight: bold; color: {{ $corTextoSuave }}; text-transform: uppercase;">Tempo do serviço</span>
                        <span style="font-size: 12px; font-weight: bold; color: {{ $corPrincipal }}; margin-left: 8px;">{{ $tempoOrdem }}</span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Participantes --}}
    @if (count($participantes) > 0)
        <div style="{{ $estiloSecao }}">
            <div style="{{ $estiloTituloSecao }}">Participantes</div>
            <div style="{{ $estiloConteudoSecao }}">
                @if (count($participantes) === 1)
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="font-weight: bold; padding-bottom: 28px; font-size: 10px; color: {{ $corTexto }};">
                                {{ $participantes[0] }}
                            </td>
                        </tr>
                        <tr>
                            <td style="border-top: 1px solid {{ $corTexto }}; padding-top: 4px; font-size: 8px; color: {{ $corTextoSuave }}; text-align: center;">
                                Assinatura
                            </td>
                        </tr>
                    </table>
                @else
                    @foreach (array_chunk($participantes, 2) as $linha)
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: {{ $loop->last ? '0' : '14px' }};">
                            <tr>
                                @foreach ($linha as $participante)
                                    <td width="50%" valign="top" style="padding: 0 10px 0 0; vertical-align: top;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="font-weight: bold; padding-bottom: 28px; font-size: 10px; color: {{ $corTexto }};">
                                                    {{ $participante }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="border-top: 1px solid {{ $corTexto }}; padding-top: 4px; font-size: 8px; color: {{ $corTextoSuave }}; text-align: center;">
                                                    Assinatura
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                @endforeach
                                @if (count($linha) === 1)
                                    <td width="50%"></td>
                                @endif
                            </tr>
                        </table>
                    @endforeach
                @endif
            </div>
        </div>
    @endif

    {{-- Observações --}}
    <div style="{{ $estiloSecao }}">
        <div style="{{ $estiloTituloSecao }}">Observações</div>
        <div style="{{ $estiloConteudoSecao }}">
            <div style="min-height: 32px; line-height: 1.55;">
                @if (! empty($ordem['observacoes']))
                    {!! $ordem['observacoes'] !!}
                @else
                    —
                @endif
            </div>
        </div>
    </div>

    {{-- Termo de concordância --}}
    <div style="{{ $estiloSecao }} margin-bottom: 0;">
        <div style="{{ $estiloTituloSecao }}">Termo de concordância do serviço</div>
        <div style="{{ $estiloConteudoSecao }}">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="58%" valign="bottom" style="font-size: 9px; color: {{ $corTextoSuave }}; line-height: 1.6; padding-right: 16px;">
                        Confirmo que o serviço foi executado conforme combinado e autorizo cobrança dos valores aqui contidos, desde que não sejam cobertos por contrato ou garantia.
                    </td>
                    <td width="42%" valign="bottom">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="height: 44px;"></td>
                            </tr>
                            <tr>
                                <td style="border-top: 1px solid {{ $corTexto }}; padding-top: 4px; font-size: 8px; color: {{ $corTextoSuave }}; text-align: center;">
                                    Assinatura do responsável
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </div>

</body>
</html>
