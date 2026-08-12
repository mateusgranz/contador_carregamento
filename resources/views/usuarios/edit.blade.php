<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('usuarios.index') }}" class="inline-flex items-center min-h-[44px] pr-2 text-gray-500 hover:text-gray-700 text-sm">← Usuários</a>
            <span class="text-gray-300">/</span>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $usuario->name }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('usuarios.update', $usuario) }}" method="POST">
                @csrf
                @method('PATCH')
                @include('usuarios.partials.form', ['usuario' => $usuario])
            </form>
        </div>
    </div>
</x-app-layout>
