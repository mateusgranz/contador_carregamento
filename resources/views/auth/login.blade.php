<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <h1 class="text-xl font-bold text-gray-800 mb-1">Entrar</h1>
    <p class="text-sm text-gray-500 mb-6">Use o código de usuário que o gestor cadastrou.</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- Código de usuário --}}
        <div>
            <x-input-label for="code" value="Código de usuário" />
            <x-text-input id="code" class="block mt-1 w-full text-lg" type="text" name="code"
                          :value="old('code')" required autofocus autocomplete="username"
                          autocapitalize="none" spellcheck="false" placeholder="Ex.: joao" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        {{-- Senha --}}
        <div class="mt-4">
            <x-input-label for="password" value="Senha" />
            <x-text-input id="password" class="block mt-1 w-full text-lg"
                          type="password" name="password"
                          required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        {{-- Manter conectado: vem marcado porque é ferramenta interna, usada no pátio --}}
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" name="remember" value="1"
                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                       @checked(old('remember', true))>
                <span class="ms-2 text-sm text-gray-700">Manter conectado neste aparelho</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-6">
            <x-primary-button class="w-full justify-center py-3 text-base">
                Entrar
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
