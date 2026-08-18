<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiquetas - Transferência #{{ $transferencia->id }}</title>
    <style>
        @page {
            size: 100mm 15mm;
            margin: 0;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        .linha {
            justify-content: center;
            height: 15mm;
            display: flex;
            page-break-after: always;
        }
        .etiqueta {
            width: 30mm;
            height: 15mm;
            margin-right: 3mm;
            text-align: center;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            background-color: #f0f0f0;
            padding-top: 3mm;
        }
        .linha .etiqueta:last-child {
            margin-right: 0;
        }
        @media print {
            .linha {
                justify-content: center;
                height: 15mm;
                display: flex;
                page-break-after: always;
            }
            .etiqueta {
                width: 30mm;
                height: 15mm;
                margin-right: 3mm;
                text-align: center;
                align-items: center;
                justify-content: center;
                font-size: 10px;
                background-color: #f0f0f0;
                padding-top: 3mm !important;
            }
            .linha .etiqueta:last-child {
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    @php $generator = new \Picqer\Barcode\BarcodeGeneratorSVG(); @endphp
    @php $contador = 0; @endphp
    <div class="linha">
        @foreach ($transferencia->movimentos as $mov)
            @for ($i = 0; $i < max(1, (int) ceil($mov->quantidade)); $i++)
                @php $contador++; @endphp
                @if ($contador > 3)
                    @php $contador = 1; @endphp
                </div><div class="linha">
                @endif
                <div class="etiqueta">
                    {!! $generator->getBarcode($mov->codigo_barras ?: '0', $generator::TYPE_CODE_128, 1.1, 30) !!}
                    <br>{{ $mov->codigo_barras }}
                </div>
            @endfor
        @endforeach
    </div>
    <script>
        window.addEventListener("load", () => { print(); });
        window.addEventListener("afterprint", () => { window.close(); });
    </script>
</body>
</html>
