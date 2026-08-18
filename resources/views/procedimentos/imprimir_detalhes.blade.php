<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Detalhes do Procedimento #{{ $prescricao->id }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; color: #000; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        h2 { font-size: 13px; margin: 0 0 14px; font-weight: normal; color: #333; }
        h3 { font-size: 14px; margin: 22px 0 8px; border-bottom: 1px solid #000; padding-bottom: 4px; }
        .info { display: flex; flex-wrap: wrap; gap: 10px 30px; margin-bottom: 6px; }
        .info div { min-width: 200px; }
        .info strong { display: block; font-size: 11px; color: #555; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #999; padding: 4px 6px; text-align: left; font-size: 11px; }
        th { background: #eee; }
        .badge { display: inline-block; padding: 1px 6px; border: 1px solid #999; border-radius: 3px; font-size: 10px; }
        .btn { display: inline-block; margin-bottom: 14px; padding: 6px 14px; border: 1px solid #000; background: #fff; cursor: pointer; }
        .log { margin-bottom: 8px; }
        .log .meta { font-size: 11px; color: #555; }
        @media print {
            .btn { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <button class="btn" onclick="window.print()">Imprimir</button>

    <h1>Detalhes do Procedimento</h1>
    <h2>Nº {{ $prescricao->codigo_versao1 ?: $prescricao->id }} — {{ now()->format('d/m/Y H:i') }}</h2>

    <div class="info">
        <div><strong>Paciente</strong>{{ $prescricao->paciente?->nm_paciente ?? '-' }}</div>
        <div><strong>CPF</strong>{{ $prescricao->paciente?->cpf ?: '-' }}</div>
        <div><strong>Clínica</strong>{{ $prescricao->clinica?->nome ?? '-' }}</div>
        <div><strong>Médico</strong>{{ $prescricao->medico ?: '-' }}</div>
        <div><strong>Data prescrição</strong>{{ $prescricao->data_prescricao?->format('d/m/Y') ?? '-' }}</div>
        <div><strong>Tipo atendimento</strong>{{ $prescricao->tipo_atendimento ?: '-' }}</div>
        <div><strong>Agendamento</strong>{{ $prescricao->agendamento ?: '-' }}</div>
        <div><strong>Semanas</strong>{{ $prescricao->qt_semanas }} ({{ $prescricao->qt_semanas_aplicacao }} com aplicação)</div>
        <div><strong>Situação</strong>{{ $prescricao->situacao }}</div>
        <div><strong>Financeiro</strong>{{ $prescricao->situacao_financeira }}</div>
        <div><strong>Valor tratamento</strong>R$ {{ valorDbForm($prescricao->valor_tratamento) }}</div>
        <div><strong>Crédito em aberto</strong>R$ {{ valorDbForm($prescricao->credito_em_aberto) }}</div>
        <div><strong>Saldo devedor</strong>R$ {{ valorDbForm($saldo) }}</div>
    </div>

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
                    <th>Medicação</th><th style="width:70px;">Qtd</th><th style="width:140px;">Aplicação</th><th style="width:120px;">Aplicado por</th>
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

    <div class="assinaturas" style="display:flex; gap:40px; margin-top:60px;">
        <div style="flex:1; text-align:center;"><hr><p>Assinatura / Carimbo</p></div>
        <div style="flex:1; text-align:center;"><hr><p>Assinatura / Carimbo</p></div>
    </div>
</body>
</html>
