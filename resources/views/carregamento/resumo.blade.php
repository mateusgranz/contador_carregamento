@php
    $produto  = $carregamento->product;
    $ehPeso   = $produto->usaPeso();
    $abrev    = $produto->unidadeAbreviada();
    $decimais = match (true) {
        $ehPeso && $produto->unidadeDiscreta() => 0,
        $produto->usaVolume()                  => 4,
        default                                => 2,
    };
    $total = (float) $carregamento->loaded_amount;

    // Texto pronto para o WhatsApp — montado aqui apenas para exibição/compartilhamento
    if ($ehPeso) {
        $linhas = $carregamento->weighings->map(fn ($p) => '• '
            .number_format((float) $p->quantity, $decimais, ',', '.').' '.$abrev
            .' ('.number_format((float) $p->weight_kg, 2, ',', '.').' kg)'
        )->implode("\n");
    } else {
        $linhas = $carregamento->loadingItems->map(fn ($item) => '• '
            .number_format((float) $item->packageType->length_cm / 100, 2, ',', '.').' m'
            .' x '.number_format((float) $item->packageType->width_mm, 0, ',', '.').' mm'
            .' — '.$item->quantity.' pct'
            .' = '.number_format((float) $item->subtotal, $decimais, ',', '.').' '.$abrev
        )->implode("\n");
    }

    $extras = $carregamento->fieldValues
        ->map(fn ($v) => $v->loadingField->label.': '.$v->valorFormatado())
        ->implode("\n");

    $textoWhatsapp = "*Carregamento finalizado*\n"
        ."Produto: {$produto->name}\n"
        ."Carregador: {$carregamento->user->name}\n"
        ."Data: ".$carregamento->finished_at->format('d/m/Y H:i')."\n"
        .($extras !== '' ? $extras."\n" : '')
        ."\n".$linhas."\n\n"
        ."*TOTAL: ".number_format($total, $decimais, ',', '.')." {$abrev}*";
@endphp

<x-carregamento-layout titulo="Carregamento finalizado" titulo-tela="Carregamento finalizado">

    <div class="max-w-3xl mx-auto px-4 py-6">

        <div class="p-5 border-4 border-green-600 bg-green-50 rounded-xl text-center">
            <p class="text-lg font-semibold text-green-800 uppercase tracking-wide">Total carregado</p>
            <p class="text-6xl font-black leading-none mt-1 tabular-nums text-green-900">
                {{ number_format($total, $decimais, ',', '.') }}
                <span class="text-3xl font-bold">{{ $abrev }}</span>
            </p>
        </div>

        <div class="mt-6 space-y-1 text-lg">
            <p><span class="font-semibold">Produto:</span> {{ $produto->name }}</p>
            <p><span class="font-semibold">Carregador:</span> {{ $carregamento->user->name }}</p>
            <p><span class="font-semibold">Finalizado em:</span>
                {{ $carregamento->finished_at->format('d/m/Y \à\s H:i') }}</p>

            {{-- Campos extras preenchidos no início do carregamento --}}
            @foreach ($carregamento->fieldValues as $valor)
                <p>
                    <span class="font-semibold">{{ $valor->loadingField->label }}:</span>
                    {{ $valor->valorFormatado() }}
                </p>
            @endforeach
        </div>

        <h2 class="text-xl font-bold mt-6 mb-3">
            {{ $ehPeso ? 'Pesagens' : 'Pacotes carregados' }}
        </h2>

        <div class="border-2 border-gray-300 rounded-xl overflow-hidden">
            @if ($ehPeso)
                @forelse ($carregamento->weighings as $pesagem)
                    <div class="flex items-center justify-between gap-3 p-4 text-lg
                                {{ ! $loop->last ? 'border-b-2 border-gray-200' : '' }}">
                        <p class="text-base text-gray-600">
                            {{ number_format((float) $pesagem->weight_kg, 2, ',', '.') }} kg na balança
                        </p>
                        <p class="font-bold tabular-nums whitespace-nowrap">
                            {{ number_format((float) $pesagem->quantity, $decimais, ',', '.') }} {{ $abrev }}
                        </p>
                    </div>
                @empty
                    <p class="p-6 text-center text-gray-600">Nenhuma pesagem registrada.</p>
                @endforelse
            @else
                @forelse ($carregamento->loadingItems as $item)
                    <div class="flex items-center justify-between gap-3 p-4 text-lg
                                {{ ! $loop->last ? 'border-b-2 border-gray-200' : '' }}">
                        <div>
                            <p class="font-semibold">
                                {{ number_format((float) $item->packageType->length_cm / 100, 2, ',', '.') }} m ·
                                {{ number_format((float) $item->packageType->width_mm, 0, ',', '.') }} mm ·
                                {{ number_format((float) $item->packageType->thickness_mm, 0, ',', '.') }} mm
                            </p>
                            <p class="text-base text-gray-600">
                                {{ $item->quantity }} {{ $item->quantity === 1 ? 'pacote' : 'pacotes' }} ×
                                {{ number_format($item->packageType->rendimentoPara($produto->calc_mode), 4, ',', '.') }} {{ $abrev }}
                            </p>
                        </div>
                        <p class="font-bold tabular-nums whitespace-nowrap">
                            {{ number_format((float) $item->subtotal, $decimais, ',', '.') }} {{ $abrev }}
                        </p>
                    </div>
                @empty
                    <p class="p-6 text-center text-gray-600">Nenhum pacote foi carregado.</p>
                @endforelse
            @endif
        </div>

        <div class="mt-8 space-y-3">
            <a href="{{ route('carregamento.pdf', $carregamento) }}"
               class="flex items-center justify-center w-full min-h-[64px] px-6 bg-gray-900 text-white text-xl font-bold rounded-xl">
                Baixar PDF
            </a>

            <button type="button" id="btn-whatsapp"
                    class="flex items-center justify-center w-full min-h-[64px] px-6 bg-green-600 text-white text-xl font-bold rounded-xl active:bg-green-700">
                Enviar no WhatsApp
            </button>

            <a href="{{ route('carregamento.index') }}"
               class="flex items-center justify-center w-full min-h-[64px] px-6 border-2 border-gray-400 text-gray-800 text-xl font-bold rounded-xl">
                Novo carregamento
            </a>
        </div>

    </div>

    <script>
        // Compartilha o PDF pelo share nativo; se indisponível, cai no link wa.me
        (function () {
            const botao = document.getElementById('btn-whatsapp');
            const texto = @json($textoWhatsapp);
            const urlPdf = @json(route('carregamento.pdf', $carregamento));
            const nomeArquivo = 'carregamento-{{ $carregamento->id }}.pdf';

            botao.addEventListener('click', async function () {
                botao.disabled = true;

                try {
                    if (navigator.canShare) {
                        const resposta = await fetch(urlPdf);
                        const blob = await resposta.blob();
                        const arquivo = new File([blob], nomeArquivo, { type: 'application/pdf' });

                        if (navigator.canShare({ files: [arquivo] })) {
                            await navigator.share({ text: texto, files: [arquivo] });
                            botao.disabled = false;
                            return;
                        }
                    }
                } catch (erro) {
                    // Compartilhamento cancelado ou indisponível — segue para o link wa.me
                }

                window.open('https://wa.me/?text=' + encodeURIComponent(texto), '_blank');
                botao.disabled = false;
            });
        })();
    </script>

</x-carregamento-layout>
