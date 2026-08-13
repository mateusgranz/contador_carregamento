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

    $nomeArquivo = $carregamento->nomeDoArquivoPdf();

    // Legenda curta que acompanha o comprovante. O detalhamento fica no PDF —
    // repetir tudo aqui só polui a conversa e quebra em telas estreitas.
    $legendaWhatsapp = "*{$produto->name}* — "
        .number_format($total, $decimais, ',', '.')." {$abrev}\n"
        .$carregamento->finished_at->format('d/m/Y \à\s H:i')
        .' · '.$carregamento->user->name;

    $identificador = $carregamento->fieldValues->first(
        fn ($v) => $v->loadingField->type === 'texto' && filled($v->value),
    );

    if ($identificador) {
        $legendaWhatsapp .= "\n{$identificador->loadingField->label}: {$identificador->valorFormatado()}";
    }
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
                    class="flex items-center justify-center w-full min-h-[64px] px-6 bg-green-600 text-white text-xl font-bold rounded-xl active:bg-green-700 disabled:opacity-60">
                Enviar no WhatsApp
            </button>

            {{-- Aparece só quando o aparelho não sabe anexar sozinho.
                 O link é tocado pelo carregador de propósito: abrir o WhatsApp
                 por script depois de baixar o arquivo é barrado como pop-up. --}}
            <div id="aviso-anexo" class="hidden bg-amber-50 border-2 border-amber-300 rounded-xl p-4">
                <p class="text-lg text-gray-800">
                    O comprovante foi baixado no aparelho. Abra o WhatsApp, toque no clipe
                    <span class="font-bold">📎</span>, escolha <span class="font-bold">Documento</span>
                    e selecione o arquivo.
                </p>
                <a id="link-whatsapp" href="{{ 'https://wa.me/?text='.rawurlencode($legendaWhatsapp) }}"
                   target="_blank" rel="noopener"
                   class="mt-4 flex items-center justify-center w-full min-h-[56px] px-6 bg-green-600 text-white text-lg font-bold rounded-xl active:bg-green-700">
                    Abrir o WhatsApp
                </a>
            </div>

            <a href="{{ route('carregamento.index') }}"
               class="flex items-center justify-center w-full min-h-[64px] px-6 border-2 border-gray-400 text-gray-800 text-xl font-bold rounded-xl">
                Novo carregamento
            </a>
        </div>

    </div>

    <script>
        // O que vai para o WhatsApp é o comprovante em PDF, nunca um texto solto.
        (function () {
            const botao = document.getElementById('btn-whatsapp');
            const aviso = document.getElementById('aviso-anexo');
            const rotulo = botao.textContent.trim();

            const urlPdf = @json(route('carregamento.pdf', $carregamento));
            const nomeArquivo = @json($nomeArquivo);
            // Legenda curta: o comprovante já traz todos os números
            const legenda = @json($legendaWhatsapp);

            // Baixa o PDF assim que a tela abre. Duas razões: o toque no botão
            // responde na hora, e o compartilhamento nativo exige ser chamado
            // dentro do gesto do usuário — esperar o download aqui o invalidaria.
            const preparo = fetch(urlPdf)
                .then((r) => (r.ok ? r.blob() : Promise.reject(r.status)))
                .then((blob) => new File([blob], nomeArquivo, { type: 'application/pdf' }))
                .catch(() => null);

            function podeAnexar(arquivo) {
                return arquivo
                    && typeof navigator.canShare === 'function'
                    && navigator.canShare({ files: [arquivo] });
            }

            botao.addEventListener('click', async function () {
                botao.disabled = true;
                botao.textContent = 'Preparando…';

                const arquivo = await preparo;

                botao.textContent = rotulo;
                botao.disabled = false;

                if (podeAnexar(arquivo)) {
                    try {
                        await navigator.share({ files: [arquivo], text: legenda });
                        return;
                    } catch (erro) {
                        // Cancelado pelo carregador: não abre mais nada
                        if (erro && erro.name === 'AbortError') {
                            return;
                        }
                    }
                }

                // Sem compartilhamento nativo (navegador antigo, ou site sem
                // HTTPS): entrega o arquivo e mostra como anexar.
                if (arquivo) {
                    const url = URL.createObjectURL(arquivo);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = nomeArquivo;
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    setTimeout(() => URL.revokeObjectURL(url), 30000);
                } else {
                    // Nem o download deu certo: abre o PDF pela rota mesmo
                    window.location.href = urlPdf;
                    return;
                }

                aviso.classList.remove('hidden');
                aviso.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        })();
    </script>

</x-carregamento-layout>
