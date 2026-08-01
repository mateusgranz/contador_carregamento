<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('usuarios.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">← Usuários</a>
            <span class="text-gray-300">/</span>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Novo Usuário</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('usuarios.store') }}" method="POST">
                @csrf
                @include('usuarios.partials.form', ['usuario' => null])
            </form>
        </div>
    </div>
</x-app-layout>
