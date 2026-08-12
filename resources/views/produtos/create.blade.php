<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('produtos.index') }}" class="inline-flex items-center min-h-[44px] pr-2 text-gray-500 hover:text-gray-700 text-sm">← Produtos</a>
            <span class="text-gray-300">/</span>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Novo Produto</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('produtos.store') }}" method="POST">
                @csrf

                {{-- Dados do Produto --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-base font-semibold text-gray-800 mb-4">Dados do Produto</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div>
                            <x-input-label for="name" value="Nome do Produto" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                          value="{{ old('name') }}" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="description" value="Descrição (opcional)" />
                            <textarea id="description" name="description" rows="2"
                                      class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">{{ old('description') }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-1" />
                        </div>

                    </div>
                </div>

                @include('produtos.partials.modalidade', ['produto' => null])

                {{-- Tipos de Pacote --}}
                <div id="secao-pacotes" class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Tipos de Pacote</h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                O m²/pacote é calculado automaticamente — não aparece no formulário.
                            </p>
                        </div>
                        <button type="button" id="btn-add-pacote"
                                class="inline-flex items-center min-h-[44px] px-4 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700 transition">
                            + Adicionar Tipo
                        </button>
                    </div>

                    @if ($errors->has('pacotes'))
                        <p class="text-sm text-red-600 mb-3">É necessário ao menos um tipo de pacote.</p>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-gray-500 uppercase border-b border-gray-200">
                                    <th class="pb-2 pr-3 font-medium">Comprimento (cm)</th>
                                    <th class="pb-2 pr-3 font-medium">Largura (mm)</th>
                                    <th class="pb-2 pr-3 font-medium">Espessura (mm)</th>
                                    <th class="pb-2 pr-3 font-medium">Qtd. Peças</th>
                                    <th class="pb-2 w-8"></th>
                                </tr>
                            </thead>
                            <tbody id="pacotes-tbody">
                                {{-- Linha inicial sempre presente --}}
                                <tr class="pacote-row">
                                    <td class="pr-3 py-2">
                                        <x-text-input type="number" name="pacotes[0][length_cm]"
                                                      step="0.01" min="0.01" class="w-full"
                                                      value="{{ old('pacotes.0.length_cm') }}" required />
                                        <x-input-error :messages="$errors->get('pacotes.0.length_cm')" class="mt-0.5 text-xs" />
                                    </td>
                                    <td class="pr-3 py-2">
                                        <x-text-input type="number" name="pacotes[0][width_mm]"
                                                      step="0.01" min="0.01" class="w-full"
                                                      value="{{ old('pacotes.0.width_mm') }}" required />
                                        <x-input-error :messages="$errors->get('pacotes.0.width_mm')" class="mt-0.5 text-xs" />
                                    </td>
                                    <td class="pr-3 py-2">
                                        <x-text-input type="number" name="pacotes[0][thickness_mm]"
                                                      step="0.01" min="0.01" class="w-full"
                                                      value="{{ old('pacotes.0.thickness_mm') }}" required />
                                        <x-input-error :messages="$errors->get('pacotes.0.thickness_mm')" class="mt-0.5 text-xs" />
                                    </td>
                                    <td class="pr-3 py-2">
                                        <x-text-input type="number" name="pacotes[0][pieces_count]"
                                                      step="1" min="1" class="w-full"
                                                      value="{{ old('pacotes.0.pieces_count') }}" required />
                                        <x-input-error :messages="$errors->get('pacotes.0.pieces_count')" class="mt-0.5 text-xs" />
                                    </td>
                                    <td class="py-2 text-center">
                                        {{-- Oculto enquanto há apenas uma linha --}}
                                        <button type="button" class="btn-remove hidden text-red-400 hover:text-red-600 text-lg leading-none px-1" title="Remover">×</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('produtos.index') }}"
                       class="inline-flex items-center min-h-[44px] px-4 bg-white border border-gray-300 text-gray-700 text-sm rounded-md hover:bg-gray-50 transition">
                        Cancelar
                    </a>
                    <x-primary-button>Salvar Produto</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Gerencia a adição e remoção dinâmica de tipos de pacote
        let proximoIndice = 1;
        const inputClasses = 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full text-sm';

        document.getElementById('btn-add-pacote').addEventListener('click', function () {
            const tbody = document.getElementById('pacotes-tbody');
            const i = proximoIndice++;
            const tr = document.createElement('tr');
            tr.className = 'pacote-row';
            tr.innerHTML = `
                <td class="pr-3 py-2">
                    <input type="number" name="pacotes[${i}][length_cm]" step="0.01" min="0.01" required class="${inputClasses}" />
                </td>
                <td class="pr-3 py-2">
                    <input type="number" name="pacotes[${i}][width_mm]" step="0.01" min="0.01" required class="${inputClasses}" />
                </td>
                <td class="pr-3 py-2">
                    <input type="number" name="pacotes[${i}][thickness_mm]" step="0.01" min="0.01" required class="${inputClasses}" />
                </td>
                <td class="pr-3 py-2">
                    <input type="number" name="pacotes[${i}][pieces_count]" step="1" min="1" required class="${inputClasses}" />
                </td>
                <td class="py-2 text-center">
                    <button type="button" class="btn-remove text-red-400 hover:text-red-600 text-lg leading-none px-1" title="Remover">×</button>
                </td>
            `;
            tbody.appendChild(tr);
            atualizarBotoesRemover();
        });

        // Delegação: captura clique em qualquer botão remover
        document.getElementById('pacotes-tbody').addEventListener('click', function (e) {
            if (e.target.classList.contains('btn-remove')) {
                e.target.closest('tr').remove();
                atualizarBotoesRemover();
            }
        });

        function atualizarBotoesRemover() {
            const rows = document.querySelectorAll('#pacotes-tbody .pacote-row');
            rows.forEach(function (row, idx) {
                const btn = row.querySelector('.btn-remove');
                if (btn) btn.classList.toggle('hidden', rows.length === 1);
            });
        }
    </script>
</x-app-layout>
