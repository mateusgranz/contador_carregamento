<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Meus dados</h2>

        <p class="mt-1 text-sm text-gray-600">
            O código de acesso é definido pelo gestor e não pode ser alterado aqui.
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="code_readonly" value="Código de usuário" />
            <x-text-input id="code_readonly" type="text" class="mt-1 block w-full bg-gray-100 font-mono"
                          :value="$user->code" disabled />
        </div>

        <div>
            <x-input-label for="name" value="Nome" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                          :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="E-mail (opcional)" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                          :value="old('email', $user->email)" autocomplete="email" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Salvar</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >Salvo.</p>
            @endif
        </div>
    </form>
</section>
