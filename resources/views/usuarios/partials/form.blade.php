@php
    $usuario = $usuario ?? null;
    $edicao  = $usuario !== null;
@endphp

<div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        <div>
            <x-input-label for="code" value="Código de usuário" />
            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full font-mono"
                          value="{{ old('code', $usuario->code ?? '') }}"
                          placeholder="Ex.: joao" required
                          autocapitalize="none" spellcheck="false" />
            <p class="text-xs text-gray-500 mt-1">
                É o que a pessoa digita para entrar. Letras minúsculas, números, ponto, hífen ou underline.
            </p>
            <x-input-error :messages="$errors->get('code')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="name" value="Nome" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                          value="{{ old('name', $usuario->name ?? '') }}"
                          placeholder="Ex.: João da Silva" required />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="password" :value="$edicao ? 'Nova senha (deixe em branco para manter)' : 'Senha'" />
            {{-- :required em vez de @if aqui dentro: diretiva Blade na tag do
                 componente quebra a leitura dos atributos e o input some --}}
            <x-text-input id="password" name="password" type="text" class="mt-1 block w-full"
                          autocomplete="new-password"
                          :required="! $edicao" />
            <p class="text-xs text-gray-500 mt-1">
                Mínimo de 6 caracteres. Fica visível para você poder anotar e passar para a pessoa.
            </p>
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="role" value="Perfil" />
            <select id="role" name="role"
                    class="mt-1 block w-full min-h-[44px] border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                <option value="carregador" @selected(old('role', $usuario->role ?? 'carregador') === 'carregador')>
                    Carregador — só a tela de carregamento
                </option>
                <option value="gestor" @selected(old('role', $usuario->role ?? '') === 'gestor')>
                    Gestor — produtos, campos e usuários
                </option>
            </select>
            <x-input-error :messages="$errors->get('role')" class="mt-1" />
        </div>

    </div>
</div>

<div class="flex items-center justify-end gap-3">
    <a href="{{ route('usuarios.index') }}"
       class="inline-flex items-center min-h-[44px] px-4 bg-white border border-gray-300 text-gray-700 text-sm rounded-md hover:bg-gray-50 transition">
        Cancelar
    </a>
    <x-primary-button>{{ $edicao ? 'Salvar Alterações' : 'Criar Usuário' }}</x-primary-button>
</div>
