@php
    $produto  = $carregamento->product;
    $ehPeso   = $produto->usaPeso();
    $abrev    = $ehPeso ? $produto->unidadeAbreviada() : 'm²';
    $decimais = $ehPeso && $produto->unidadeDiscreta() ? 0 : 2;
    $total    = (float) ($ehPeso ? $carregamento->loaded_qty : $carregamento->loaded_sqm);
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Carregamento #{{ $carregamento->id }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 12px; color: #111; margin: 0; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .cabecalho { border-bottom: 2px solid #111; padding-bottom: 10px; margin-bottom: 16px; }
        .dados td { padding: 3px 0; font-size: 12px; }
        .dados .rotulo { font-weight: bold; width: 110px; }
        table.itens { width: 100%; border-collapse: collapse; margin-top: 14px; }
        table.itens th {
            text-align: left; font-size: 11px; text-transform: uppercase;
            border-bottom: 2px solid #111; padding: 6px 4px;
        }
        table.itens td { padding: 7px 4px; border-bottom: 1px solid #ddd; }
        .num { text-align: right; }
        .total { margin-top: 18px; border-top: 2px solid #111; padding-top: 10px; }
        .total .valor { font-size: 22px; font-weight: bold; text-align: right; }
        .rodape { margin-top: 34px; font-size: 10px; color: #666; text-align: center; }
    </style>
</head>
<body>

    <div class="cabecalho">
        <h1>Comprovante de Carregamento</h1>
        <div style="font-size: 11px; color: #555;">Carregamento nº {{ $carregamento->id }}</div>
    </div>

    <table class="dados">
        <tr>
            <td class="rotulo">Produto</td>
            <td>
                {{ $produto->name }}
                @if ($ehPeso)
                    (por peso · {{ number_format((float) $produto->kg_per_unit, 4, ',', '.') }} kg
                    por {{ $produto->unidadeLabel(1) }})
                @else
                    ({{ $produto->unidadeAbreviada() }})
                @endif
            </td>
        </tr>
        <tr>
            <td class="rotulo">Carregador</td>
            <td>{{ $carregamento->user->name }}</td>
        </tr>
        <tr>
            <td class="rotulo">Data e hora</td>
            <td>{{ $carregamento->finished_at->format('d/m/Y \à\s H:i') }}</td>
        </tr>
        {{-- Campos extras definidos pelo gestor --}}
        @foreach ($carregamento->fieldValues as $valor)
            <tr>
                <td class="rotulo">{{ $valor->loadingField->label }}</td>
                <td>{{ $valor->valorFormatado() }}</td>
            </tr>
        @endforeach
    </table>

    @if ($ehPeso)
        <table class="itens">
            <thead>
                <tr>
                    <th>Pesagem</th>
                    <th class="num">Peso na balança</th>
                    <th class="num">Quantidade</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($carregamento->weighings as $pesagem)
                    <tr>
                        <td>{{ $loop->iteration }}ª</td>
                        <td class="num">{{ number_format((float) $pesagem->weight_kg, 2, ',', '.') }} kg</td>
                        <td class="num">
                            {{ number_format((float) $pesagem->quantity, $decimais, ',', '.') }} {{ $abrev }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <table class="itens">
            <thead>
                <tr>
                    <th>Tipo de pacote</th>
                    <th class="num">Pacotes</th>
                    <th class="num">m² / pacote</th>
                    <th class="num">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($carregamento->loadingItems as $item)
                    <tr>
                        <td>
                            {{ number_format((float) $item->packageType->length_cm / 100, 2, ',', '.') }} m ×
                            {{ number_format((float) $item->packageType->width_mm, 0, ',', '.') }} mm ×
                            {{ number_format((float) $item->packageType->thickness_mm, 0, ',', '.') }} mm
                            — {{ $item->packageType->pieces_count }} peças
                        </td>
                        <td class="num">{{ $item->quantity }}</td>
                        <td class="num">{{ number_format((float) $item->packageType->sqm_per_package, 4, ',', '.') }}</td>
                        <td class="num">{{ number_format((float) $item->subtotal_sqm, 2, ',', '.') }} m²</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="total">
        <div class="valor">
            Total carregado: {{ number_format($total, $decimais, ',', '.') }} {{ $abrev }}
        </div>
    </div>

    <div class="rodape">
        Documento gerado em {{ now()->format('d/m/Y H:i') }}
    </div>

</body>
</html>
