<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Prontuário #{{ $prescricao->id }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #000; margin: 10mm; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        h2 { font-size: 12px; margin: 0 0 12px; font-weight: normal; color: #333; }
        h3 { font-size: 13px; margin: 18px 0 6px; border-bottom: 1px solid #000; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; page-break-inside: auto; }
        tr { page-break-inside: avoid; }
        th, td { border: 1px solid #999; padding: 3px 5px; text-align: left; font-size: 10px; }
        th { background: #eee; }
        .info { margin-bottom: 6px; }
        .info td { border: none; padding: 2px 8px 2px 0; font-size: 11px; }
        .info .rotulo { color: #555; font-size: 9px; text-transform: uppercase; display: block; }
        .badge { display: inline-block; padding: 0 4px; border: 1px solid #999; border-radius: 3px; font-size: 9px; }
        .log { margin-bottom: 6px; }
        .log .meta { font-size: 9px; color: #555; }
        .assinaturas { margin-top: 50px; }
        .assinaturas td { border: none; text-align: center; }
        .linha-ass { border-top: 1px solid #000; margin-top: 30px; padding-top: 4px; font-size: 10px; }
    </style>
</head>
<body>
    <h1>Prontuário — Detalhes do Procedimento</h1>
    <h2>Nº {{ $prescricao->codigo_versao1 ?: $prescricao->id }} — Gerado em {{ now()->format('d/m/Y H:i') }}</h2>

    <table class="info">
        <tr>
            <td><span class="rotulo">Paciente</span>{{ $prescricao->paciente?->nm_paciente ?? '-' }}</td>
            <td><span class="rotulo">CPF</span>{{ $prescricao->paciente?->cpf ?: '-' }}</td>
            <td><span class="rotulo">Clínica</span>{{ $prescricao->clinica?->nome ?? '-' }}</td>
        </tr>
        <tr>
            <td><span class="rotulo">Médico</span>{{ $prescricao->medico ?: '-' }}</td>
            <td><span class="rotulo">Data prescrição</span>{{ $prescricao->data_prescricao?->format('d/m/Y') ?? '-' }}</td>
            <td><span class="rotulo">Tipo atendimento</span>{{ $prescricao->tipo_atendimento ?: '-' }}</td>
        </tr>
        <tr>
            <td><span class="rotulo">Agendamento</span>{{ $prescricao->agendamento ?: '-' }}</td>
            <td><span class="rotulo">Semanas</span>{{ $prescricao->qt_semanas }} ({{ $prescricao->qt_semanas_aplicacao }} com aplicação)</td>
            <td><span class="rotulo">Situação</span>{{ $prescricao->situacao }}</td>
        </tr>
        <tr>
            <td><span class="rotulo">Financeiro</span>{{ $prescricao->situacao_financeira }}</td>
            <td><span class="rotulo">Valor tratamento</span>R$ {{ valorDbForm($prescricao->valor_tratamento) }}</td>
            <td><span class="rotulo">Crédito em aberto</span>R$ {{ valorDbForm($prescricao->credito_em_aberto) }}</td>
        </tr>
        <tr>
            <td><span class="rotulo">Saldo devedor</span>R$ {{ valorDbForm($saldo) }}</td>
            <td colspan="2"></td>
        </tr>
    </table>

    @if ($prescricao->obs)
        <p><strong>Observações gerais:</strong> {{ $prescricao->obs }}</p>
    @endif

    <h3>Semanas e Medicações</h3>
    @forelse ($prescricao->semanas->sortBy('nr_semana') as $semana)
        <table>
            <thead>
                <tr>
                    <th colspan="4">
                        Semana {{ $semana->nr_semana }} — {{ $semana->data_prevista?->format('d/m/Y') }}
                        <span class="badge">{{ $semana->situacao }}</span>
                    </th>
                </tr>
                <tr>
                    <th>Medicação</th><th style="width:60px;">Qtd</th><th style="width:120px;">Aplicação</th><th style="width:110px;">Aplicado por</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($semana->medicamentos as $med)
                    <tr>
                        <td>
                            @if ($med->is_soro)
                                <span class="badge">Soro</span> {{ $med->soro?->nome ?? $med->medicamento?->nome ?? '-' }}
                            @elseif ($med->combo_id)
                                <span class="badge">Combo</span> {{ $med->combo?->nome ?? '-' }}
                            @else
                                {{ $med->medicamento?->nome ?? '-' }}
                            @endif
                        </td>
                        <td>{{ $med->quantidade }}</td>
                        <td>{{ $med->aplicado_em?->format('d/m/Y H:i') ?? 'Não aplicada' }}</td>
                        <td>{{ $med->userAplicacao?->nome ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Sem aplicação (pausa)</td></tr>
                @endforelse
            </tbody>
        </table>
    @empty
        <p>Nenhuma semana gerada.</p>
    @endforelse

    <h3>Financeiro (Parcelas)</h3>
    <table>
        <thead>
            <tr>
                <th>Nr</th><th>Semana</th><th>Vencimento</th><th style="text-align:right;">Valor</th><th style="text-align:right;">Pago</th><th style="text-align:right;">Saldo</th><th>Situação</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($prescricao->financeiroParcelas->sortBy('nr_parcela') as $parcela)
                <tr>
                    <td>{{ $parcela->nr_parcela }}</td>
                    <td>{{ $parcela->semana?->nr_semana ? 'Semana '.$parcela->semana->nr_semana : '-' }}</td>
                    <td>{{ $parcela->dt_vencimento?->format('d/m/Y') ?? '-' }}</td>
                    <td style="text-align:right;">R$ {{ valorDbForm($parcela->valor_parcela) }}</td>
                    <td style="text-align:right;">R$ {{ valorDbForm($parcela->valor_pago) }}</td>
                    <td style="text-align:right;">R$ {{ valorDbForm(max(0, (float) $parcela->valor_parcela - (float) $parcela->valor_pago)) }}</td>
                    <td>{{ $parcela->situacao }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Nenhuma parcela.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h3>Pagamentos</h3>
    <table>
        <thead>
            <tr>
                <th>Data</th><th>Usuário</th><th style="text-align:right;">Valor</th><th>Formas</th><th>Obs</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($prescricao->pagamentos->sortBy('dt_pagamento') as $pag)
                <tr>
                    <td>{{ $pag->dt_pagamento?->format('d/m/Y') }}</td>
                    <td>{{ $pag->user?->nome ?? '-' }}</td>
                    <td style="text-align:right;">R$ {{ valorDbForm($pag->vl_total) }}</td>
                    <td>{{ $pag->formas->pluck('forma_pagamento')->unique()->implode(', ') ?: '-' }}</td>
                    <td>{{ $pag->obs ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Nenhum pagamento.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h3>Histórico de Alterações</h3>
    @forelse ($prescricao->logs->sortByDesc('id') as $log)
        <div class="log">
            <span class="meta">{{ $log->created_at?->format('d/m/Y H:i') }} — {{ $log->user?->nome ?? 'Sistema' }}</span>
            <div>{{ $log->descricao }}</div>
        </div>
    @empty
        <p>Sem alterações registradas.</p>
    @endforelse

    @if ($prescricao->observacoes->isNotEmpty())
        <h3>Observações</h3>
        @foreach ($prescricao->observacoes->sortByDesc('id') as $obs)
            <div class="log">
                <span class="meta">{{ $obs->created_at?->format('d/m/Y H:i') }} — {{ $obs->user?->nome ?? 'Sistema' }}</span>
                <div>{{ $obs->observacao }}</div>
            </div>
        @endforeach
    @endif

    <table class="assinaturas">
        <tr>
            <td><div class="linha-ass">Assinatura / Carimbo</div></td>
            <td><div class="linha-ass">Assinatura / Carimbo</div></td>
        </tr>
    </table>
</body>
</html>
