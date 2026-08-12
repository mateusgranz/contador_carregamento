<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Campos do Carregamento</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @if (session('sucesso'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg text-sm">
                    {{ session('sucesso') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg text-sm">
                    @foreach ($errors->all() as $erro)
                        <p>{{ $erro }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Novo campo --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-base font-semibold text-gray-800">Novo Campo</h3>
                <p class="text-xs text-gray-500 mt-0.5 mb-4">
                    Informação extra que o carregador preenche antes de começar a carregar.
                </p>

                <form action="{{ route('campos.store') }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">

                        <div class="sm:col-span-5">
                            <x-input-label for="label" value="Nome do campo" />
                            <x-text-input id="label" name="label" type="text" class="mt-1 block w-full"
                                          value="{{ old('label') }}" placeholder="Ex.: Código do pedido" required />
                        </div>

                        <div class="sm:col-span-3">
                            <x-input-label for="type" value="Tipo" />
                            <select id="type" name="type"
                                    class="mt-1 block w-full min-h-[44px] border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                <option value="texto"  @selected(old('type') === 'texto')>Texto</option>
                                <option value="numero" @selected(old('type') === 'numero')>Número</option>
                                <option value="data"   @selected(old('type') === 'data')>Data</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            {{-- min-h e checkbox maior: no celular o alvo de toque
                                 era de 16px, impossível de acertar com precisão --}}
                            <label class="inline-flex items-center gap-2 min-h-[44px] text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="required" value="1" @checked(old('required'))
                                       class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                Obrigatório
                            </label>
                        </div>

                        <div class="sm:col-span-2 flex justify-end">
                            <x-primary-button>Adicionar</x-primary-button>
                        </div>

                    </div>
                </form>
            </div>

            {{-- Campos cadastrados --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-base font-semibold text-gray-800 mb-4">Campos Cadastrados</h3>

                @if ($campos->isEmpty())
                    <p class="text-sm text-gray-400 italic">
                        Nenhum campo cadastrado. O carregador só informa a metragem.
                    </p>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach ($campos as $campo)
                            <div class="flex items-center justify-between gap-4 py-4">

                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900">{{ $campo->label }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ ['texto' => 'Texto', 'numero' => 'Número', 'data' => 'Data'][$campo->type] }}
                                        ·
                                        @if ($campo->required)
                                            <span class="text-amber-700 font-medium">Obrigatório</span>
                                        @else
                                            Opcional
                                        @endif
                                    </p>
                                </div>

                                <div class="flex items-center gap-4 shrink-0">

                                    {{-- Toggle: liga/desliga a exibição na tela do carregador --}}
                                    <form action="{{ route('campos.update', $campo) }}" method="POST"
                                          id="toggle-{{ $campo->id }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="active" value="{{ $campo->active ? 0 : 1 }}">
                                        {{-- O botão tem 44px de altura para o toque,
                                             mas o trilho visual continua com 24px --}}
                                        <button type="submit"
                                                role="switch"
                                                aria-checked="{{ $campo->active ? 'true' : 'false' }}"
                                                aria-label="{{ $campo->active ? 'Desativar' : 'Ativar' }} {{ $campo->label }}"
                                                class="inline-flex items-center justify-center min-h-[44px] px-1">
                                            <span class="relative inline-flex h-6 w-11 items-center rounded-full transition
                                                         {{ $campo->active ? 'bg-green-600' : 'bg-gray-300' }}">
                                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition
                                                             {{ $campo->active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                            </span>
                                        </button>
                                    </form>

                                    <span class="text-xs w-16 {{ $campo->active ? 'text-green-700 font-medium' : 'text-gray-400' }}">
                                        {{ $campo->active ? 'Ativo' : 'Inativo' }}
                                    </span>

                                    <button type="submit" form="excluir-{{ $campo->id }}"
                                            class="inline-flex items-center min-h-[44px] px-2 text-red-500 hover:text-red-700 text-sm transition">
                                        Excluir
                                    </button>

                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- Forms de exclusão fora dos demais forms: form aninhado é HTML inválido --}}
    @foreach ($campos as $campo)
        <form id="excluir-{{ $campo->id }}" action="{{ route('campos.destroy', $campo) }}" method="POST"
              class="hidden"
              onsubmit="return confirm('Excluir o campo &quot;{{ $campo->label }}&quot;? Os dados já preenchidos em carregamentos anteriores também serão apagados.')">
            @csrf
            @method('DELETE')
        </form>
    @endforeach

</x-app-layout>
