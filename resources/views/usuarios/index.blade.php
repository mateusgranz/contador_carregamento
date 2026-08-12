<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Usuários</h2>
            <a href="{{ route('usuarios.create') }}"
               class="inline-flex items-center min-h-[44px] px-4 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700 transition">
                + Novo Usuário
            </a>
        </div>
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

            {{-- Celular: cartões empilhados. A tabela não cabe em 390px e as
                 ações ficavam cortadas pelo overflow, sem como alcançá-las. --}}
            <div class="sm:hidden space-y-3">
                @foreach ($usuarios as $usuario)
                    <div class="bg-white shadow-sm rounded-lg p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-mono font-semibold text-gray-900 text-base">
                                    {{ $usuario->code }}
                                    @if ($usuario->is(auth()->user()))
                                        <span class="text-xs text-gray-400 font-sans">(você)</span>
                                    @endif
                                </p>
                                <p class="text-gray-800 mt-0.5">{{ $usuario->name }}</p>
                            </div>
                            <span class="shrink-0 px-2 py-1 rounded text-xs font-semibold
                                         {{ $usuario->role === 'gestor'
                                            ? 'text-indigo-700 bg-indigo-50'
                                            : 'text-emerald-700 bg-emerald-50' }}">
                                {{ $usuario->role === 'gestor' ? 'Gestor' : 'Carregador' }}
                            </span>
                        </div>

                        <div class="flex gap-2 mt-4">
                            <a href="{{ route('usuarios.edit', $usuario) }}"
                               class="flex-1 inline-flex items-center justify-center min-h-[44px] bg-indigo-50 text-indigo-700 font-medium rounded hover:bg-indigo-100 transition">
                                Editar
                            </a>
                            @unless ($usuario->is(auth()->user()))
                                <button type="submit" form="excluir-usuario-{{ $usuario->id }}"
                                        class="flex-1 inline-flex items-center justify-center min-h-[44px] bg-red-50 text-red-700 font-medium rounded hover:bg-red-100 transition">
                                    Excluir
                                </button>
                            @endunless
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Desktop: a tabela, que aqui cabe folgada --}}
            <div class="hidden sm:block bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold text-gray-700">Código</th>
                            <th class="px-6 py-3 font-semibold text-gray-700">Nome</th>
                            <th class="px-6 py-3 font-semibold text-gray-700">Perfil</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($usuarios as $usuario)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <span class="font-mono font-semibold text-gray-900">{{ $usuario->code }}</span>
                                    @if ($usuario->is(auth()->user()))
                                        <span class="ml-1 text-xs text-gray-400">(você)</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-800">{{ $usuario->name }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold
                                                 {{ $usuario->role === 'gestor'
                                                    ? 'text-indigo-700 bg-indigo-50'
                                                    : 'text-emerald-700 bg-emerald-50' }}">
                                        {{ $usuario->role === 'gestor' ? 'Gestor' : 'Carregador' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                    <a href="{{ route('usuarios.edit', $usuario) }}"
                                       class="inline-flex items-center min-h-[44px] px-4 bg-indigo-50 text-indigo-700 text-sm rounded hover:bg-indigo-100 transition">
                                        Editar
                                    </a>
                                    @unless ($usuario->is(auth()->user()))
                                        <button type="submit" form="excluir-usuario-{{ $usuario->id }}"
                                                class="inline-flex items-center min-h-[44px] px-4 bg-red-50 text-red-700 text-sm rounded hover:bg-red-100 transition">
                                            Excluir
                                        </button>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    {{-- Forms de exclusão fora da tabela: form aninhado é HTML inválido --}}
    @foreach ($usuarios as $usuario)
        @unless ($usuario->is(auth()->user()))
            <form id="excluir-usuario-{{ $usuario->id }}" action="{{ route('usuarios.destroy', $usuario) }}"
                  method="POST" class="hidden"
                  onsubmit="return confirm('Excluir {{ addslashes($usuario->name) }}? Os carregamentos feitos por ele também serão apagados.')">
                @csrf
                @method('DELETE')
            </form>
        @endunless
    @endforeach

</x-app-layout>
