@php
    use App\Models\Product;

    // Valor atual: o que voltou da validação, senão o do produto, senão pacote
    $modoAtual    = old('calc_mode', $produto->calc_mode ?? 'pacote');
    $unidadeAtual = old('unit', $produto->unit ?? 'm2');
    $kgAtual      = old('kg_per_unit', $produto->kg_per_unit ?? null);
@endphp

<div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
    <h3 class="text-base font-semibold text-gray-800">Modalidade de Cálculo</h3>
    <p class="text-xs text-gray-500 mt-0.5 mb-4">
        Define como o carregador vai contar este produto no pátio.
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

        <label class="block cursor-pointer">
            <input type="radio" name="calc_mode" value="pacote" class="peer sr-only"
                   @checked($modoAtual === 'pacote')>
            <div class="h-full p-4 border-2 border-gray-200 rounded-lg peer-checked:border-indigo-600 peer-checked:bg-indigo-50">
                <p class="font-semibold text-gray-900">Por área (m²)</p>
                <p class="text-xs text-gray-600 mt-1">
                    Conta pacotes e acumula m², usando largura e comprimento.
                    Use para forro e deck.
                </p>
            </div>
        </label>

        <label class="block cursor-pointer">
            <input type="radio" name="calc_mode" value="volume" class="peer sr-only"
                   @checked($modoAtual === 'volume')>
            <div class="h-full p-4 border-2 border-gray-200 rounded-lg peer-checked:border-indigo-600 peer-checked:bg-indigo-50">
                <p class="font-semibold text-gray-900">Por volume (m³)</p>
                <p class="text-xs text-gray-600 mt-1">
                    Conta pacotes e acumula m³, entrando também a espessura.
                    Use para tábua e madeira serrada.
                </p>
            </div>
        </label>

        <label class="block cursor-pointer">
            <input type="radio" name="calc_mode" value="peso" class="peer sr-only"
                   @checked($modoAtual === 'peso')>
            <div class="h-full p-4 border-2 border-gray-200 rounded-lg peer-checked:border-indigo-600 peer-checked:bg-indigo-50">
                <p class="font-semibold text-gray-900">Por peso</p>
                <p class="text-xs text-gray-600 mt-1">
                    Pesa na balança e converte na unidade de venda.
                    Use para bobina de zinco, barra de ferro.
                </p>
            </div>
        </label>

    </div>

    <x-input-error :messages="$errors->get('calc_mode')" class="mt-2" />

    <div class="mt-5 pt-5 border-t border-gray-200 grid grid-cols-1 sm:grid-cols-2 gap-4">

        <div>
            <x-input-label for="unit" value="Unidade de Venda" />
            <select id="unit" name="unit"
                    class="mt-1 block w-full min-h-[44px] border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                @foreach (Product::UNIDADES as $codigo => [$abrev, $singular, $plural])
                    {{-- data-modos diz em quais modalidades esta unidade faz sentido --}}
                    <option value="{{ $codigo }}"
                            data-modos="{{ collect(['pacote', 'volume', 'peso'])
                                ->filter(fn ($modo) => array_key_exists($codigo, Product::unidadesPara($modo)))
                                ->implode(' ') }}"
                            @selected($unidadeAtual === $codigo)>
                        {{ ucfirst($singular) }} ({{ $abrev }})
                    </option>
                @endforeach
            </select>
            <p id="aviso-unidade" class="mt-1 text-xs text-gray-500 hidden">
                Contando pacotes a unidade é definida pela conta: m² por área, m³ por volume.
            </p>
            <x-input-error :messages="$errors->get('unit')" class="mt-1" />
        </div>

        {{-- Fator de conversão: exclusivo do modo peso --}}
        <div id="config-peso" class="{{ $modoAtual === 'peso' ? '' : 'hidden' }}">
            <x-input-label for="kg_per_unit" value="Peso de cada unidade (kg)" />
            <x-text-input id="kg_per_unit" name="kg_per_unit" type="number" step="0.0001" min="0.0001"
                          class="mt-1 block w-full" value="{{ $kgAtual }}" placeholder="Ex.: 1,02" />
            <p id="previa-peso" class="mt-1 text-xs text-gray-600"></p>
            <x-input-error :messages="$errors->get('kg_per_unit')" class="mt-1" />
        </div>

    </div>

    <p id="aviso-pacotes" class="mt-4 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-md p-3 {{ $modoAtual === 'peso' ? '' : 'hidden' }}">
        Produtos por peso não usam tipos de pacote. Ao salvar nesta modalidade,
        os tipos de pacote cadastrados neste produto são removidos.
    </p>
</div>

<script>
    // Mostra a seção certa conforme a modalidade escolhida.
    // Espera o DOM porque #secao-pacotes vem depois deste include no HTML.
    document.addEventListener('DOMContentLoaded', function () {
        const configPeso    = document.getElementById('config-peso');
        const avisoPacotes  = document.getElementById('aviso-pacotes');
        const avisoUnidade  = document.getElementById('aviso-unidade');
        const secaoPacotes  = document.getElementById('secao-pacotes');
        const previa        = document.getElementById('previa-peso');
        const unidade       = document.getElementById('unit');
        const kg            = document.getElementById('kg_per_unit');

        function modoAtual() {
            return document.querySelector('input[name="calc_mode"]:checked')?.value ?? 'pacote';
        }

        function aplicarModo() {
            const modo = modoAtual();
            const peso = modo === 'peso';

            configPeso.classList.toggle('hidden', !peso);
            avisoPacotes.classList.toggle('hidden', !peso);
            avisoUnidade.classList.toggle('hidden', peso);

            if (secaoPacotes) {
                secaoPacotes.classList.toggle('hidden', peso);
            }

            // Campo escondido com required trava o envio do formulário no navegador,
            // e campo desabilitado não é enviado nem validado — por isso os dois passos
            desabilitar(secaoPacotes, peso);
            kg.disabled = !peso;

            filtrarUnidades(modo);
            atualizarPrevia();
        }

        // Contando pacotes a unidade vem da conta: m² por área, m³ por volume
        function filtrarUnidades(modo) {
            let primeiraValida = null;
            let precisaTrocar  = false;

            Array.from(unidade.options).forEach(function (opcao) {
                const permitida = (opcao.dataset.modos ?? '').split(' ').includes(modo);

                opcao.hidden   = ! permitida;
                opcao.disabled = ! permitida;

                if (permitida && primeiraValida === null) {
                    primeiraValida = opcao.value;
                }

                if (opcao.selected && ! permitida) {
                    precisaTrocar = true;
                }
            });

            if (precisaTrocar && primeiraValida !== null) {
                unidade.value = primeiraValida;
            }
        }

        function desabilitar(secao, desligar) {
            if (! secao) {
                return;
            }

            secao.querySelectorAll('input, select, textarea, button').forEach(function (campo) {
                campo.disabled = desligar;
            });
        }

        function atualizarPrevia() {
            const valor = parseFloat(kg.value);
            const nome  = unidade.options[unidade.selectedIndex]?.text ?? 'unidade';

            previa.textContent = valor > 0 && modoAtual() === 'peso'
                ? `1 ${nome} = ${valor.toLocaleString('pt-BR', { minimumFractionDigits: 2 })} kg`
                : '';
        }

        document.querySelectorAll('input[name="calc_mode"]').forEach(function (radio) {
            radio.addEventListener('change', aplicarModo);
        });

        unidade.addEventListener('change', atualizarPrevia);
        kg.addEventListener('input', atualizarPrevia);

        aplicarModo();
    });
</script>
