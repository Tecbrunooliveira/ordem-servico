<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Ordem de Serviço {{ $numeroOrdem }}</title>
</head>
@php
    $corPrincipal = '#005300';
    $corTexto = '#000000';
    $raioSecao = '8px';
    $raioBadge = '5px';
    $estiloSecao = "border: 1px solid {$corPrincipal}; border-radius: {$raioSecao}; overflow: hidden; margin-bottom: 14px;";
    $estiloTituloSecao = "background-color: {$corPrincipal}; color: #ffffff; font-size: 10px; font-weight: bold; padding: 7px 12px; text-transform: uppercase;";
    $estiloConteudoSecao = "padding: 12px 14px; color: {$corTexto}; font-size: 10px;";
@endphp
<body style="font-family: DejaVu Sans, sans-serif; font-size: 11px; color: {{ $corTexto }}; margin: 0; padding: 22px 26px; line-height: 1.4;">

    {{-- Cabeçalho --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="border-bottom: 2px solid {{ $corPrincipal }}; padding-bottom: 12px; margin-bottom: 16px;">
        <tr>
            <td width="78" valign="top" style="padding-right: 10px;">
                @if (! empty($empresa['logo']))
                    <img src="{{ $empresa['logo'] }}" width="70" height="70" alt="Logo" style="display: block; border-radius: {{ $raioSecao }};">
                @else
                    <table width="70" height="70" cellpadding="0" cellspacing="0" style="border: 1px solid {{ $corPrincipal }}; border-radius: {{ $raioSecao }};">
                        <tr>
                            <td align="center" valign="middle" style="font-size: 9px; color: {{ $corTexto }};">LOGO</td>
                        </tr>
                    </table>
                @endif
            </td>
            <td valign="top" style="padding-right: 12px;">
                <div style="font-size: 14px; font-weight: bold; color: {{ $corTexto }}; margin-bottom: 4px;">
                    {{ ! empty($empresa['razao_social']) ? $empresa['razao_social'] : $empresa['nome_empresa'] }}
                </div>
                <div style="font-size: 10px; color: {{ $corTexto }}; line-height: 1.5;">
                    @if (! empty($empresa['cnpj']))
                        CNPJ: {{ $empresa['cnpj'] }}<br>
                    @endif
                    @if (! empty($empresa['endereco']))
                        {{ $empresa['endereco'] }}<br>
                    @endif
                    @if (! empty($empresa['cidade']) || ! empty($empresa['estado']) || ! empty($empresa['cep']))
                        {{ trim(($empresa['cidade'] ?? '').(! empty($empresa['estado']) ? ' — '.$empresa['estado'] : '').(! empty($empresa['cep']) ? ' — CEP '.$empresa['cep'] : '')) }}<br>
                    @endif
                    @if (! empty($empresa['telefone']))
                        Tel: {{ $empresa['telefone'] }}
                        @if (! empty($empresa['email']))
                            &nbsp;|&nbsp; {{ $empresa['email'] }}
                        @endif
                        <br>
                    @endif
                    @if (! empty($empresa['site']))
                        {{ $empresa['site'] }}
                    @endif
                </div>
            </td>
            <td width="210" valign="top" align="right">
                <div style="font-size: 17px; font-weight: bold; color: {{ $corTexto }}; letter-spacing: 0.5px; margin-bottom: 8px;">ORDEM DE SERVIÇO</div>
                <div style="font-size: 10px; line-height: 1.7; text-align: right; color: {{ $corTexto }};">
                    <div>
                        <span style="font-weight: bold;">Nº da Ordem:</span> {{ $numeroOrdem }}
                    </div>
                    <div>
                        <span style="font-weight: bold;">Data:</span> {{ $dataDocumento }}
                    </div>
                    <div>
                        <span style="font-weight: bold;">Técnico:</span> {{ $tecnico }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Dados do cliente --}}
    <div style="{{ $estiloSecao }}">
        <div style="{{ $estiloTituloSecao }}">Dados do cliente</div>
        <div style="{{ $estiloConteudoSecao }}">
            <table width="100%" cellpadding="5" cellspacing="0" style="font-size: 10px; color: {{ $corTexto }};">
                <tr>
                    <td width="50%" valign="top" style="padding-right: 10px;">
                        <span style="font-weight: bold;">Nome:</span> {{ $cliente['nome'] ?? '—' }}
                    </td>
                    <td width="50%" valign="top" style="padding-left: 10px;">
                        <span style="font-weight: bold;">CNPJ:</span> {{ $cliente['documento'] ?? '—' }}
                    </td>
                </tr>
                <tr>
                    <td width="50%" valign="top" style="padding-right: 10px;">
                        <span style="font-weight: bold;">Telefone:</span> {{ $cliente['telefone'] ?? '—' }}
                    </td>
                    <td width="50%" valign="top" style="padding-left: 10px;">
                        <span style="font-weight: bold;">E-mail:</span> {{ $cliente['email'] ?? '—' }}
                    </td>
                </tr>
                <tr>
                    <td colspan="2" valign="top" style="padding-top: 4px;">
                        <span style="font-weight: bold;">Endereço:</span> {{ $clienteEndereco }}
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
                    <td style="border: 1px solid {{ $corPrincipal }}; border-radius: {{ $raioBadge }}; font-size: 9px; font-weight: bold; padding: 3px 8px; color: {{ $corTexto }};">
                        {{ $tipo }}
                    </td>
                </tr>
            </table>
            @if (! empty($tituloChamado))
                <div style="font-weight: bold; margin-bottom: 6px; color: {{ $corTexto }};">{{ $tituloChamado }}</div>
            @endif
            <div style="font-size: 10px; color: {{ $corTexto }};">
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
        <div style="{{ $estiloConteudoSecao }} min-height: 56px;">
            <div style="padding-bottom: 10px;">
                @if (! empty($ordem['descricao_servicos']))
                    {!! $ordem['descricao_servicos'] !!}
                @else
                    —
                @endif
            </div>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 8px; border-top: 1px solid #e8ece8;">
                <tr>
                    <td></td>
                    <td align="right" valign="bottom" style="padding-top: 8px; white-space: nowrap;">
                        <span style="font-size: 9px; color: {{ $corTexto }};">Tempo do Serviço:</span>
                        <span style="font-size: 11px; font-weight: bold; color: {{ $corTexto }}; margin-left: 6px;">{{ $tempoOrdem }}</span>
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
                            <td style="font-weight: bold; padding-bottom: 22px; font-size: 11px; color: {{ $corTexto }};">
                                {{ $participantes[0] }}
                            </td>
                        </tr>
                        <tr>
                            <td style="border-top: 1px solid {{ $corPrincipal }}; padding-top: 4px; font-size: 9px; color: {{ $corTexto }}; text-align: center;">
                                Assinatura
                            </td>
                        </tr>
                    </table>
                @else
                    @foreach (array_chunk($participantes, 2) as $linha)
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: {{ $loop->last ? '0' : '16px' }};">
                            <tr>
                                @foreach ($linha as $participante)
                                    <td width="50%" valign="top" style="padding: 0 8px 0 0; vertical-align: top;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="font-weight: bold; padding-bottom: 22px; font-size: 11px; color: {{ $corTexto }};">
                                                    {{ $participante }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="border-top: 1px solid {{ $corPrincipal }}; padding-top: 4px; font-size: 9px; color: {{ $corTexto }}; text-align: center;">
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
        <div style="{{ $estiloConteudoSecao }} min-height: 36px;">
            @if (! empty($ordem['observacoes']))
                {!! $ordem['observacoes'] !!}
            @else
                —
            @endif
        </div>
    </div>

    {{-- Termo de concordância --}}
    <div style="{{ $estiloSecao }} margin-bottom: 0;">
        <div style="{{ $estiloTituloSecao }}">Termo de concordância do serviço</div>
        <div style="{{ $estiloConteudoSecao }}">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="58%" valign="bottom" style="font-size: 10px; color: {{ $corTexto }}; line-height: 1.55; padding-right: 16px;">
                        Confirmo que o serviço foi executado conforme combinado e autorizo cobrança dos valores aqui contidos, desde que não sejam cobertos por contrato ou garantia.
                    </td>
                    <td width="42%" valign="bottom">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="height: 42px;"></td>
                            </tr>
                            <tr>
                                <td style="border-top: 1px solid {{ $corPrincipal }}; padding-top: 4px; font-size: 9px; color: {{ $corTexto }}; text-align: center;">
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
