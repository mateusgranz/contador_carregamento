<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'gestor';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code'     => ['required', 'string', 'max:50', 'regex:/^[a-z0-9._-]+$/', Rule::unique('users', 'code')],
            'name'     => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
            'role'     => ['required', 'in:gestor,carregador'],
        ];
    }

    /**
     * O código é sempre minúsculo e sem espaços — é o que o operador digita.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => Str::lower(trim((string) $this->input('code'))),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Informe o código de usuário.',
            'code.unique'   => 'Já existe um usuário com esse código.',
            'code.regex'    => 'O código aceita apenas letras, números, ponto, hífen e underline.',
            'name.required' => 'Informe o nome do usuário.',
            'password.min'  => 'A senha precisa ter ao menos 6 caracteres.',
            'role.required' => 'Escolha o perfil do usuário.',
        ];
    }
}
