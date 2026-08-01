<x-carregamento-layout titulo="Quantos m²" titulo-tela="Passo 2 de 3">

    <div class="max-w-3xl mx-auto px-4 py-6">

        <a href="{{ route('carregamento.index') }}"
           class="inline-flex items-center gap-2 text-lg text-gray-600 mb-5">
            <span class="text-2xl">‹</span> Trocar produto
        </a>

        <div class="p-5 border-2 border-gray-300 rounded-xl mb-6">
            <p class="text-base text-gray-600 uppercase tracking-wide font-semibold">Produto</p>
            <p class="text-2xl font-bold">{{ $produto->name }}</p>
        </div>

        <form method="POST" action="{{ route('carregamento.store') }}">
            @csrf
            <input type="hidden" name="product_id" value="{{ $produto->id }}">

            <label for="quantidade" class="block text-2xl font-bold mb-1">
                Quantos {{ $produto->unidadeLabel() }} você vai carregar?
            </label>
            <p class="text-lg text-gray-600 mb-4">
                @if ($produto->usaPeso())
                    É a quantidade do pedido. O sistema converte para kg na balança.
                @else
                    É a metragem do pedido. O sistema conta os pacotes para você.
                @endif
            </p>

            <input type="number"
                   step="{{ $produto->unidadeDiscreta() ? '1' : '0.01' }}"
                   min="{{ $produto->unidadeDiscreta() ? '1' : '0.01' }}"
                   inputmode="decimal"
                   id="quantidade" name="quantidade" value="{{ old('quantidade') }}"
                   placeholder="{{ $produto->unidadeDiscreta() ? '0' : '0,00' }}" required autofocus
                   class="w-full h-24 px-4 text-center text-5xl font-black tabular-nums
                          border-4 border-gray-400 rounded-xl focus:border-gray-900 focus:ring-0">

            {{-- Campos extras definidos pelo gestor --}}
            @foreach ($campos as $campo)
                <div class="mt-6">
                    <label for="campo-{{ $campo->id }}" class="block text-xl font-bold">
                        {{ $campo->label }}
                        @unless ($campo->required)
                            <span class="font-normal text-gray-500">(opcional)</span>
                        @endunless
                    </label>

                    <input type="{{ $campo->tipoInput() }}"
                           @if ($campo->type === 'numero') step="any" inputmode="decimal" @endif
                           id="campo-{{ $campo->id }}"
                           name="campos[{{ $campo->id }}]"
                           value="{{ old("campos.{$campo->id}") }}"
                           @required($campo->required)
                           class="mt-2 w-full h-16 px-4 text-2xl
                                  border-2 rounded-xl focus:border-gray-900 focus:ring-0
                                  {{ $errors->has("campos.{$campo->id}") ? 'border-red-500' : 'border-gray-400' }}">

                    @error("campos.{$campo->id}")
                        <p class="mt-1 text-lg font-semibold text-red-700">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <button type="submit"
                    class="mt-6 w-full min-h-[72px] px-6 bg-green-600 text-white text-2xl font-bold rounded-xl active:bg-green-700">
                Começar a carregar
            </button>
        </form>

    </div>

</x-carregamento-layout>
