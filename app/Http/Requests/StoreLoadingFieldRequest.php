<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoadingFieldRequest extends FormRequest
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
            'label'    => ['required', 'string', 'max:100'],
            'type'     => ['required', 'in:texto,numero,data'],
            'required' => ['boolean'],
            'active'   => ['boolean'],
        ];
    }

    /**
     * Checkbox não marcado não é enviado pelo navegador.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'required' => $this->boolean('required'),
            'active'   => $this->boolean('active'),
        ]);
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
