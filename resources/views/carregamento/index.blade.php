<x-carregamento-layout titulo="Escolher produto" titulo-tela="Passo 1 de 3">

    <div class="max-w-3xl mx-auto px-4 py-6">

        @if ($emAndamento)
            <div class="mb-6 p-5 border-4 border-amber-400 bg-amber-50 rounded-xl">
                <p class="text-xl font-bold text-amber-900">Você tem um carregamento em andamento</p>
                <p class="text-lg text-amber-900 mt-1">
                    {{ $emAndamento->product->name }} —
                    {{ number_format((float) $emAndamento->loaded_sqm, 2, ',', '.') }} m² já carregados
                </p>
                <a href="{{ route('carregamento.show', $emAndamento) }}"
                   class="mt-4 flex items-center justify-center w-full min-h-[64px] px-6 bg-amber-500 text-white text-xl font-bold rounded-xl">
                    Continuar esse carregamento
                </a>
            </div>
        @endif

        <h2 class="text-2xl font-bold mb-1">O que você vai carregar?</h2>
        <p class="text-lg text-gray-600 mb-4">Toque no produto.</p>

        @if ($produtos->isEmpty())
            <div class="p-6 border-2 border-gray-300 rounded-xl text-center text-gray-700 text-lg">
                Nenhum produto disponível.
                Peça ao gestor para cadastrar os produtos e pacotes.
            </div>
        @else
            <div class="space-y-3">
                @foreach ($produtos as $produto)
                    {{-- Um toque leva direto ao passo 2 --}}
                    <a href="{{ route('carregamento.quantidade', $produto) }}"
                       class="flex items-center justify-between gap-4 p-5 min-h-[80px]
                              border-2 border-gray-400 rounded-xl active:bg-gray-100">
                        <span>
                            <span class="block text-2xl font-bold">{{ $produto->name }}</span>
                            <span class="block text-base text-gray-600 mt-0.5">
                                @if ($produto->usaPeso())
                                    Por peso ·
                                    {{ number_format((float) $produto->kg_per_unit, 4, ',', '.') }} kg
                                    por {{ $produto->unidadeLabel(1) }}
                                @else
                                    {{ $produto->package_types_count }}
                                    {{ $produto->package_types_count === 1 ? 'tipo de pacote' : 'tipos de pacote' }}
                                @endif
                            </span>
                        </span>
                        <span class="text-3xl text-gray-400 shrink-0">›</span>
                    </a>
                @endforeach
            </div>
        @endif

    </div>

</x-carregamento-layout>
