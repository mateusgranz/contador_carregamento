<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLoadingFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'gestor';
    }

    /**
     * O toggle envia só o campo `active`; a edição completa envia todos.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if ($this->apenasToggle()) {
            return ['active' => ['required', 'boolean']];
        }

        return [
            'label'    => ['required', 'string', 'max:100'],
            'type'     => ['required', 'in:texto,numero,data'],
            'required' => ['boolean'],
            'active'   => ['boolean'],
        ];
    }

    /**
     * Indica se a requisição é só a troca do toggle de ativação.
     */
    public function apenasToggle(): bool
    {
        return ! $this->has('label');
    }

    protected function prepareForValidation(): void
    {
        $dados = ['active' => $this->boolean('active')];

        if (! $this->apenasToggle()) {
            $dados['required'] = $this->boolean('required');
        }

        $this->merge($dados);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'label' => 'nome do campo',
            'type'  => 'tipo do campo',
        ];
    }
}
