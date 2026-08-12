@props([
    'titulo'     => 'Carregamento',
    'tituloTela' => 'Carregamento',
])
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    {{-- Sem maximum-scale: bloquear o pinch-zoom é barreira de acessibilidade.
         As fontes já são grandes; quem precisar aproximar, consegue. --}}
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $titulo }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{-- Fundo branco e alto contraste: a tela é usada sob sol no pátio --}}
<body class="font-sans antialiased bg-white text-gray-900 text-lg">

    <div class="min-h-screen flex flex-col">

        <header class="bg-white border-b-2 border-gray-200">
            <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-base text-gray-500 leading-tight">{{ auth()->user()->name }}</p>
                    <p class="text-xl font-bold truncate">{{ $tituloTela }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="px-4 py-3 text-base font-semibold text-gray-600 border-2 border-gray-300 rounded-lg">
                        Sair
                    </button>
                </form>
            </div>
        </header>

        @if (session('sucesso'))
            <div class="max-w-3xl mx-auto w-full px-4 pt-4">
                <div class="p-4 bg-green-100 text-green-900 text-lg font-semibold rounded-lg border-2 border-green-300">
                    {{ session('sucesso') }}
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="max-w-3xl mx-auto w-full px-4 pt-4">
                <div class="p-4 bg-red-100 text-red-900 text-lg font-semibold rounded-lg border-2 border-red-300">
                    @foreach ($errors->all() as $erro)
                        <p>{{ $erro }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        <main class="flex-1">
            {{ $slot }}
        </main>

    </div>

</body>
</html>
