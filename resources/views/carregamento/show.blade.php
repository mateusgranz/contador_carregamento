<x-carregamento-layout titulo="Carregando" :titulo-tela="$carregamento->product->name">

    {{-- Total sempre visível no topo, em destaque --}}
    <div class="sticky top-0 z-10 bg-white border-b-4 border-gray-900">
        <div class="max-w-3xl mx-auto px-4 py-4 text-center">
            <p class="text-lg font-semibold text-gray-600 uppercase tracking-wide">Total carregado</p>
            <p class="text-6xl font-black leading-none mt-1 tabular-nums">
                {{ number_format((float) $carregamento->loaded_sqm, 2, ',', '.') }}
                <span class="text-3xl font-bold">m²</span>
            </p>

            @if ($restante !== null)
                <p class="text-lg font-semibold mt-2 {{ $restante > 0 ? 'text-gray-700' : 'text-green-700' }}">
                    @if ($restante > 0)
                        Faltam {{ number_format($restante, 2, ',', '.') }} m²
                        de {{ number_format((float) $carregamento->target_sqm, 2, ',', '.') }} m²
                    @else
                        Pedido de {{ number_format((float) $carregamento->target_sqm, 2, ',', '.') }} m² completo
                    @endif
                </p>
            @endif
        </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 py-5">

        <div class="space-y-4">
            @foreach ($tipos as $tipo)
                @php
                    $quantidade = $quantidades[$tipo->id] ?? 0;
                    $subtotal   = (float) $tipo->sqm_per_package * $quantidade;
                @endphp

                {{-- scroll-mt evita que o cabeçalho fixo cubra o card ao voltar ancorado --}}
                <div id="tipo-{{ $tipo->id }}" class="scroll-mt-44 rounded-xl p-4 border-2 border-gray-300">

                    <p class="text-xl font-bold">
                        {{ number_format((float) $tipo->length_cm / 100, 2, ',', '.') }} m ·
                        {{ number_format((float) $tipo->width_mm, 0, ',', '.') }} mm ·
                        {{ number_format((float) $tipo->thickness_mm, 0, ',', '.') }} mm
                    </p>
                    <p class="text-lg text-gray-700">{{ $tipo->pieces_count }} peças por pacote</p>

                    <div class="flex items-center justify-between gap-3 mt-3">

                        {{-- Remover 1 pacote --}}
                        <form method="POST"
                              action="{{ route('carregamento.itens.destroy', [$carregamento, $tipo]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    @disabled($quantidade === 0)
                                    class="w-20 h-16 text-4xl font-black rounded-xl border-2
                                           {{ $quantidade === 0
                                              ? 'bg-gray-100 text-gray-300 border-gray-200'
                                              : 'bg-white text-red-700 border-red-400 active:bg-red-100' }}"
                                    aria-label="Remover um pacote">
                                −
                            </button>
                        </form>

                        <div class="text-center flex-1">
                            <p class="text-4xl font-black leading-none tabular-nums">{{ $quantidade }}</p>
                            <p class="text-base font-semibold text-gray-600">
                                {{ $quantidade === 1 ? 'pacote' : 'pacotes' }}
                            </p>
                        </div>

                        {{-- Adicionar 1 pacote --}}
                        <form method="POST" action="{{ route('carregamento.itens.store', $carregamento) }}">
                            @csrf
                            <input type="hidden" name="package_type_id" value="{{ $tipo->id }}">
                            <button type="submit"
                                    class="w-20 h-16 text-4xl font-black rounded-xl border-2 bg-green-600 text-white border-green-700 active:bg-green-700"
                                    aria-label="Adicionar um pacote">
                                +
                            </button>
                        </form>

                    </div>

                    {{-- m² do pacote sempre visível embaixo do botão --}}
                    <div class="mt-3 pt-3 border-t-2 border-gray-200 flex items-baseline justify-between gap-2">
                        <p class="text-lg font-semibold">
                            {{ number_format((float) $tipo->sqm_per_package, 4, ',', '.') }} m² por pacote
                        </p>
                        <p class="text-lg font-bold tabular-nums">
                            {{ number_format($subtotal, 2, ',', '.') }} m²
                        </p>
                    </div>

                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('carregamento.finalizar', $carregamento) }}"
              class="mt-8"
              onsubmit="return confirm('Finalizar este carregamento? Depois não será possível alterar os pacotes.')">
            @csrf
            <button type="submit"
                    class="w-full min-h-[64px] px-6 bg-gray-900 text-white text-2xl font-bold rounded-xl active:bg-gray-700">
                Finalizar carregamento
            </button>
        </form>

    </div>

    {{-- Aviso de fechamento: aparece quando falta menos de um pacote para completar o pedido.
         A sugestão não é obrigatória — se a medida não estiver disponível no pátio,
         o carregador fecha a janela e adiciona qualquer outro pacote. --}}
    @if ($ideal)
        <div id="aviso-fechamento"
             class="hidden fixed inset-0 z-50 bg-black/60 flex items-end sm:items-center justify-center p-4">
            <div class="bg-white w-full max-w-md rounded-2xl border-4 border-green-600 p-6">

                <p class="text-2xl font-black text-green-800">Falta pouco!</p>
                <p class="text-lg text-gray-800 mt-2">
                    Faltam <span class="font-bold">{{ number_format($restante, 2, ',', '.') }} m²</span>
                    para completar o pedido.
                </p>

                <div class="mt-4 p-4 border-2 border-green-600 bg-green-50 rounded-xl text-center">
                    <p class="text-lg font-semibold text-green-900">Basta mais 1 pacote de</p>
                    <p class="text-3xl font-black text-green-900 mt-1">
                        {{ number_format((float) $ideal->length_cm / 100, 2, ',', '.') }} m ·
                        {{ number_format((float) $ideal->width_mm, 0, ',', '.') }} mm
                    </p>
                    <p class="text-lg text-green-900 mt-1">
                        {{ number_format((float) $ideal->thickness_mm, 0, ',', '.') }} mm de espessura ·
                        {{ number_format((float) $ideal->sqm_per_package, 4, ',', '.') }} m²
                    </p>
                </div>

                <form method="POST" action="{{ route('carregamento.itens.store', $carregamento) }}" class="mt-5">
                    @csrf
                    <input type="hidden" name="package_type_id" value="{{ $ideal->id }}">
                    <button type="submit"
                            class="w-full min-h-[64px] px-6 bg-green-600 text-white text-xl font-bold rounded-xl active:bg-green-700">
                        Adicionar esse pacote
                    </button>
                </form>

                <button type="button" id="btn-fechar-aviso"
                        class="mt-3 w-full min-h-[64px] px-6 border-2 border-gray-400 text-gray-800 text-xl font-bold rounded-xl">
                    Não tenho essa medida
                </button>

            </div>
        </div>

        <script>
            // Reexibe o aviso a cada mudança do que falta, mas respeita quem já dispensou aquele estado
            (function () {
                const aviso = document.getElementById('aviso-fechamento');
                const chave = 'aviso-{{ $carregamento->id }}-{{ number_format($restante, 2, '.', '') }}';

                if (sessionStorage.getItem(chave) !== 'dispensado') {
                    aviso.classList.remove('hidden');
                }

                document.getElementById('btn-fechar-aviso').addEventListener('click', function () {
                    sessionStorage.setItem(chave, 'dispensado');
                    aviso.classList.add('hidden');
                });
            })();
        </script>
    @endif

</x-carregamento-layout>
