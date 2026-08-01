<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'gestor';
    }

    /**
     * A senha só é trocada quando o gestor digita uma nova.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $usuario = $this->route('usuario');

        return [
            'code'     => ['required', 'string', 'max:50', 'regex:/^[a-z0-9._-]+$/', Rule::unique('users', 'code')->ignore($usuario)],
            'name'     => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:6'],
            'role'     => ['required', 'in:gestor,carregador'],
        ];
    }

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
        ];
    }
}
