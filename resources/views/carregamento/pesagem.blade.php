@php
    $abrev    = $produto->unidadeAbreviada();
    $decimais = $produto->unidadeDiscreta() ? 0 : 2;
    $completo = $restante <= 0;
@endphp

<x-carregamento-layout titulo="Pesagem" :titulo-tela="$carregamento->product->name">

    {{-- Alvo sempre visível no topo: quanto falta e quanto isso dá na balança --}}
    <div class="sticky top-0 z-10 bg-white border-b-4 border-gray-900">
        <div class="max-w-3xl mx-auto px-4 py-4 text-center">

            @if ($completo)
                <p class="text-lg font-semibold text-green-700 uppercase tracking-wide">Pedido completo</p>
                <p class="text-5xl font-black leading-none mt-1 tabular-nums text-green-700">
                    {{ number_format((float) $carregamento->loaded_amount, $decimais, ',', '.') }}
                    <span class="text-2xl font-bold">{{ $abrev }}</span>
                </p>
            @else
                <p class="text-lg font-semibold text-gray-600 uppercase tracking-wide">Ainda falta</p>
                <p class="text-6xl font-black leading-none mt-1 tabular-nums">
                    {{ number_format($restante, $decimais, ',', '.') }}
                    <span class="text-3xl font-bold">{{ $abrev }}</span>
                </p>
                <p class="text-xl font-bold text-gray-800 mt-2">
                    ≈ {{ number_format($pesoAlvoKg, 2, ',', '.') }} kg na balança
                </p>
            @endif

            <p class="text-base text-gray-600 mt-2">
                Pedido: {{ number_format((float) $carregamento->target_amount, $decimais, ',', '.') }} {{ $abrev }}
                · Já separado: {{ number_format((float) $carregamento->loaded_amount, $decimais, ',', '.') }} {{ $abrev }}
            </p>
        </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 py-5">

        @if ($calculo)
            {{-- Resultado do cálculo: nada foi gravado ainda --}}
            @php
                $sobrou = $calculo['excedente'] > 0;
                $exato  = abs($calculo['excedente']) < 0.005;
            @endphp

            <div class="rounded-xl border-4 {{ $sobrou && ! $exato ? 'border-amber-500 bg-amber-50' : 'border-green-600 bg-green-50' }} p-5">

                <p class="text-xl font-bold text-center">
                    {{ number_format($calculo['peso_kg'], 2, ',', '.') }} kg
                    =
                    {{ number_format($calculo['quantidade'], $decimais, ',', '.') }} {{ $abrev }}
                </p>

                @if ($exato)
                    <div class="mt-4 p-4 bg-white border-2 border-green-600 rounded-xl text-center">
                        <p class="text-2xl font-black text-green-800">Está exato!</p>
                        <p class="text-lg text-green-900 mt-1">Pode entregar assim.</p>
                    </div>
                @elseif ($sobrou)
                    <div class="mt-4 p-4 bg-white border-2 border-amber-500 rounded-xl text-center">
                        <p class="text-lg font-semibold text-amber-900 uppercase tracking-wide">Retire</p>
                        <p class="text-5xl font-black text-amber-900 leading-none mt-1 tabular-nums">
                            {{ number_format($calculo['excedente'], $decimais, ',', '.') }}
                            <span class="text-2xl">{{ $abrev }}</span>
                        </p>
                        <p class="text-xl font-bold text-amber-900 mt-2">
                            ({{ number_format($calculo['excedente_kg'], 2, ',', '.') }} kg)
                        </p>
                        <p class="text-lg text-amber-900 mt-3 border-t-2 border-amber-200 pt-3">
                            Deixe a balança marcar<br>
                            <span class="text-3xl font-black">{{ number_format($calculo['peso_alvo_kg'], 2, ',', '.') }} kg</span>
                        </p>
                    </div>
                @else
                    <div class="mt-4 p-4 bg-white border-2 border-green-600 rounded-xl text-center">
                        <p class="text-lg font-semibold text-green-900 uppercase tracking-wide">Ainda vai faltar</p>
                        <p class="text-5xl font-black text-green-900 leading-none mt-1 tabular-nums">
                            {{ number_format(abs($calculo['excedente']), $decimais, ',', '.') }}
                            <span class="text-2xl">{{ $abrev }}</span>
                        </p>
                        <p class="text-lg text-green-900 mt-2">
                            Registre e pese a próxima.
                        </p>
                    </div>
                @endif

                {{-- O que registrar --}}
                <div class="mt-5 space-y-3">
                    @if ($sobrou && ! $exato)
                        <form method="POST" action="{{ route('carregamento.pesagens.store', $carregamento) }}">
                            @csrf
                            <input type="hidden" name="weight_kg" value="{{ $calculo['peso_alvo_kg'] }}">
                            <input type="hidden" name="quantity" value="{{ $calculo['quantidade_alvo'] }}">
                            <button type="submit"
                                    class="w-full min-h-[64px] px-4 bg-green-600 text-white text-xl font-bold rounded-xl active:bg-green-700">
                                Já retirei — registrar
                                {{ number_format($calculo['quantidade_alvo'], $decimais, ',', '.') }} {{ $abrev }}
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('carregamento.pesagens.store', $carregamento) }}">
                        @csrf
                        <input type="hidden" name="weight_kg" value="{{ $calculo['peso_kg'] }}">
                        <input type="hidden" name="quantity" value="{{ $calculo['quantidade'] }}">
                        <button type="submit"
                                class="w-full min-h-[64px] px-4 text-xl font-bold rounded-xl border-2
                                       {{ $sobrou && ! $exato
                                          ? 'border-gray-400 text-gray-800'
                                          : 'bg-green-600 text-white border-green-700 active:bg-green-700' }}">
                            Registrar {{ number_format($calculo['quantidade'], $decimais, ',', '.') }} {{ $abrev }}
                            @if ($sobrou && ! $exato) (tudo) @endif
                        </button>
                    </form>

                    <a href="{{ route('carregamento.show', $carregamento) }}"
                       class="flex items-center justify-center w-full min-h-[64px] px-4 border-2 border-gray-400 text-gray-800 text-xl font-bold rounded-xl">
                        Pesar de novo
                    </a>
                </div>
            </div>
        @else
            {{-- Entrada do peso: o cálculo é um GET, nada é gravado --}}
            <form method="GET" action="{{ route('carregamento.show', $carregamento) }}">
                <label for="peso" class="block text-2xl font-bold">Quanto deu na balança?</label>
                <p class="text-lg text-gray-600 mt-1 mb-3">
                    Cada {{ $produto->unidadeLabel(1) }} pesa
                    {{ number_format((float) $produto->kg_per_unit, 4, ',', '.') }} kg.
                </p>

                <div class="flex items-center gap-3">
                    <input type="number" step="0.01" min="0.01" inputmode="decimal"
                           id="peso" name="peso" placeholder="0,00" required autofocus
                           class="flex-1 h-24 px-4 text-center text-5xl font-black tabular-nums
                                  border-4 border-gray-400 rounded-xl focus:border-gray-900 focus:ring-0">
                    <span class="text-3xl font-black text-gray-600">kg</span>
                </div>

                <button type="submit"
                        class="mt-5 w-full min-h-[72px] px-6 bg-gray-900 text-white text-2xl font-bold rounded-xl active:bg-gray-700">
                    Calcular
                </button>
            </form>
        @endif

        {{-- Pesagens já registradas --}}
        @if ($pesagens->isNotEmpty())
            <h2 class="text-xl font-bold mt-8 mb-3">Já separado</h2>

            <div class="space-y-3">
                @foreach ($pesagens as $pesagem)
                    <div class="flex items-center justify-between gap-3 p-4 border-2 border-gray-300 rounded-xl">
                        <div>
                            <p class="text-xl font-bold tabular-nums">
                                {{ number_format((float) $pesagem->quantity, $decimais, ',', '.') }} {{ $abrev }}
                            </p>
                            <p class="text-base text-gray-600">
                                {{ number_format((float) $pesagem->weight_kg, 2, ',', '.') }} kg na balança
                            </p>
                        </div>

                        <button type="submit" form="remover-pesagem-{{ $pesagem->id }}"
                                class="w-20 h-16 text-2xl font-black rounded-xl border-2 bg-white text-red-700 border-red-400 active:bg-red-100"
                                aria-label="Remover esta pesagem">
                            ✕
                        </button>
                    </div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('carregamento.finalizar', $carregamento) }}"
              class="mt-8"
              onsubmit="return confirm('Finalizar este carregamento? Depois não será possível alterar as pesagens.')">
            @csrf
            <button type="submit"
                    class="w-full min-h-[64px] px-6 bg-gray-900 text-white text-2xl font-bold rounded-xl active:bg-gray-700">
                Finalizar carregamento
            </button>
        </form>

    </div>

    {{-- Forms de remoção fora dos demais: form aninhado é HTML inválido --}}
    @foreach ($pesagens as $pesagem)
        <form id="remover-pesagem-{{ $pesagem->id }}"
              action="{{ route('carregamento.pesagens.destroy', [$carregamento, $pesagem]) }}"
              method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endforeach

</x-carregamento-layout>
