<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Produtos</h2>
            <a href="{{ route('produtos.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700 transition">
                + Novo Produto
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('sucesso'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">
                    {{ session('sucesso') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($produtos->isEmpty())
                    <div class="p-10 text-center text-gray-500">
                        Nenhum produto cadastrado ainda.
                        <a href="{{ route('produtos.create') }}" class="text-indigo-600 hover:underline ml-1">Criar o primeiro.</a>
                    </div>
                @else
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 font-semibold text-gray-700">Nome</th>
                                <th class="px-6 py-3 font-semibold text-gray-700">Unidade</th>
                                <th class="px-6 py-3 font-semibold text-gray-700">Modalidade</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($produtos as $produto)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900">{{ $produto->name }}</td>
                                    <td class="px-6 py-4">
                                        <span class="font-semibold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded text-xs">
                                            {{ $produto->unidadeAbreviada() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">
                                        @if ($produto->usaPeso())
                                            Por peso —
                                            {{ number_format((float) $produto->kg_per_unit, 4, ',', '.') }} kg
                                            por {{ $produto->unidadeLabel(1) }}
                                        @else
                                            {{ $produto->package_types_count }}
                                            {{ $produto->package_types_count === 1 ? 'tipo de pacote' : 'tipos de pacote' }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                        <a href="{{ route('produtos.edit', $produto) }}"
                                           class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 text-sm rounded hover:bg-indigo-100 transition">
                                            Editar
                                        </a>
                                        <form action="{{ route('produtos.destroy', $produto) }}" method="POST"
                                              class="inline"
                                              onsubmit="return confirm('Excluir {{ addslashes($produto->name) }} e todos os seus tipos de pacote?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-700 text-sm rounded hover:bg-red-100 transition">
                                                Excluir
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
