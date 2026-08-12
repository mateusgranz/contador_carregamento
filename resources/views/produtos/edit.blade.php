<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('produtos.index') }}" class="inline-flex items-center min-h-[44px] pr-2 text-gray-500 hover:text-gray-700 text-sm">← Produtos</a>
            <span class="text-gray-300">/</span>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $produto->name }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @if (session('sucesso'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg text-sm">
                    {{ session('sucesso') }}
                </div>
            @endif

            <form action="{{ route('produtos.update', $produto) }}" method="POST">
                @csrf
                @method('PATCH')

                {{-- Dados do Produto --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-base font-semibold text-gray-800 mb-4">Dados do Produto</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div>
                            <x-input-label for="name" value="Nome do Produto" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                          value="{{ old('name', $produto->name) }}" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="description" value="Descrição (opcional)" />
                            <textarea id="description" name="description" rows="2"
                                      class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">{{ old('description', $produto->description) }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-1" />
                        </div>

                    </div>
                </div>

                @include('produtos.partials.modalidade', ['produto' => $produto])

                <div id="secao-pacotes">

                {{-- Tipos de Pacote Existentes --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-base font-semibold text-gray-800 mb-4">Tipos de Pacote Cadastrados</h3>

                    @if ($produto->packageTypes->isEmpty())
                        <p class="text-sm text-gray-400 italic">Nenhum tipo de pacote cadastrado.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs text-gray-500 uppercase border-b border-gray-200">
                                        <th class="pb-2 pr-4 font-medium">Comprimento (cm)</th>
                                        <th class="pb-2 pr-4 font-medium">Largura (mm)</th>
                                        <th class="pb-2 pr-4 font-medium">Espessura (mm)</th>
                                        <th class="pb-2 pr-4 font-medium">Qtd. Peças</th>
                                        <th class="pb-2 pr-4 font-medium">m²/pacote</th>
                                        <th class="pb-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($produto->packageTypes as $pacote)
                                        <tr>
                                            <td class="pr-4 py-2.5 text-gray-800">{{ $pacote->length_cm }}</td>
                                            <td class="pr-4 py-2.5 text-gray-800">{{ $pacote->width_mm }}</td>
                                            <td class="pr-4 py-2.5 text-gray-800">{{ $pacote->thickness_mm }}</td>
                                            <td class="pr-4 py-2.5 text-gray-800">{{ $pacote->pieces_count }}</td>
                                            <td class="pr-4 py-2.5 font-semibold text-indigo-700">
                                                {{ number_format($pacote->sqm_per_package, 4) }} m²
                                            </td>
                                            <td class="py-2.5">
                                                {{-- O form correspondente fica fora do form de edição: form aninhado é HTML inválido --}}
                                                <button type="submit" form="remover-pacote-{{ $pacote->id }}"
                                                        class="inline-flex items-center min-h-[44px] px-2 text-red-500 hover:text-red-700 text-sm transition">
                                                    Remover
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Adicionar Novos Tipos de Pacote --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Adicionar Novos Tipos de Pacote</h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                O m²/pacote é calculado automaticamente.
                            </p>
                        </div>
                        <button type="button" id="btn-add-pacote"
                                class="inline-flex items-center min-h-[44px] px-4 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700 transition">
                            + Adicionar Tipo
                        </button>
                    </div>

                    <div id="novos-pacotes-container">
                        <p id="msg-vazio" class="text-sm text-gray-400 italic">
                            Clique em "+ Adicionar Tipo" para incluir novos tipos de pacote.
                        </p>
                    </div>
                </div>

                </div>{{-- /secao-pacotes --}}

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('produtos.index') }}"
                       class="inline-flex items-center min-h-[44px] px-4 bg-white border border-gray-300 text-gray-700 text-sm rounded-md hover:bg-gray-50 transition">
                        Cancelar
                    </a>
                    <x-primary-button>Salvar Alterações</x-primary-button>
                </div>
            </form>

            {{-- Remoção de tipos de pacote: forms separados, acionados pelo atributo form= dos botões da tabela --}}
            @foreach ($produto->packageTypes as $pacote)
                <form id="remover-pacote-{{ $pacote->id }}"
                      action="{{ route('pacotes.destroy', [$produto, $pacote]) }}"
                      method="POST"
                      class="hidden"
                      onsubmit="return confirm('Remover este tipo de pacote?')">
                    @csrf
                    @method('DELETE')
                </form>
            @endforeach

        </div>
    </div>

    <script>
        // Adiciona novos tipos de pacote ao formulário de edição
        let proximoIndice = 0;
        const inputClasses = 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full text-sm';
        const containerNovos = document.getElementById('novos-pacotes-container');
        const msgVazio = document.getElementById('msg-vazio');

        document.getElementById('btn-add-pacote').addEventListener('click', function () {
            msgVazio.classList.add('hidden');
            const i = proximoIndice++;
            const div = document.createElement('div');
            div.className = 'pacote-novo grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 mb-3 border border-gray-200 rounded-lg bg-gray-50';
            div.innerHTML = `
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Comprimento (cm)</label>
                    <input type="number" name="pacotes[${i}][length_cm]" step="0.01" min="0.01" required class="${inputClasses}" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Largura (mm)</label>
                    <input type="number" name="pacotes[${i}][width_mm]" step="0.01" min="0.01" required class="${inputClasses}" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Espessura (mm)</label>
                    <input type="number" name="pacotes[${i}][thickness_mm]" step="0.01" min="0.01" required class="${inputClasses}" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Qtd. Peças</label>
                    <input type="number" name="pacotes[${i}][pieces_count]" step="1" min="1" required class="${inputClasses}" />
                </div>
                <div class="col-span-2 sm:col-span-4 flex justify-end">
                    <button type="button" onclick="removerNovoPacote(this)"
                            class="text-red-400 hover:text-red-600 text-sm hover:underline transition">
                        Remover este tipo
                    </button>
                </div>
            `;
            containerNovos.appendChild(div);
        });

        function removerNovoPacote(btn) {
            btn.closest('.pacote-novo').remove();
            if (containerNovos.querySelectorAll('.pacote-novo').length === 0) {
                msgVazio.classList.remove('hidden');
            }
        }
    </script>
</x-app-layout>
