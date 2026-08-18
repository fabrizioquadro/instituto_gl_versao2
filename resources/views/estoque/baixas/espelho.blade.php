<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Espelho de Baixa #{{ $baixa->id }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; color: #000; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 0 0 14px; font-weight: normal; }
        .info { display: flex; justify-content: space-between; gap: 20px; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 8px 0; margin-bottom: 14px; }
        .info div { flex: 1; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 4px 6px; text-align: left; }
        th { background: #eee; }
        .assinaturas { display: flex; gap: 40px; margin-top: 60px; }
        .assinatura { flex: 1; text-align: center; }
        .assinatura hr { border: none; border-top: 1px solid #000; margin-bottom: 4px; }
        .btn { display: inline-block; margin-bottom: 14px; padding: 6px 14px; border: 1px solid #000; background: #fff; cursor: pointer; }
        @media print {
            .btn { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <button class="btn" onclick="window.print()">Imprimir</button>
    <h1>Espelho de Baixa</h1>
    <h2>Nº {{ $baixa->id }} — {{ $baixa->data ? \Carbon\Carbon::parse($baixa->data)->format('d/m/Y H:i') : '-' }}</h2>

    <div class="info">
        <div><strong>Clínica:</strong> {{ $baixa->clinica?->nome ?? '-' }}</div>
        <div><strong>Responsável:</strong> {{ $baixa->user?->nome ?? '-' }}</div>
        <div><strong>Motivo:</strong> {{ $baixa->motivo ?? '-' }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Medicamento</th>
                <th>Lote</th>
                <th>Código de Barras</th>
                <th style="text-align:right;">Quantidade</th>
                <th style="text-align:right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($baixa->movimentos as $mov)
                <tr>
                    <td>{{ $mov->medicamento?->nome ?? '-' }}</td>
                    <td>{{ $mov->lote }}</td>
                    <td>{{ $mov->codigo_barras ?: '-' }}</td>
                    <td style="text-align:right;">{{ number_format($mov->quantidade, 2, ',', '.') }}</td>
                    <td style="text-align:right;">R$ {{ valorDbForm($mov->total) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Nenhum item.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="assinaturas">
        <div class="assinatura">
            <hr>
            <p>Assinatura do Colaborador (Entrega)</p>
        </div>
        <div class="assinatura">
            <hr>
            <p>Assinatura do Responsável (Recebimento)</p>
        </div>
    </div>

    <script>
        window.addEventListener("load", () => { print(); });
    </script>
</body>
</html>
