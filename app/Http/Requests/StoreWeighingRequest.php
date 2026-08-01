<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWeighingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'carregador';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'weight_kg' => ['required', 'numeric', 'min:0.0001', 'max:999999.9999'],
            // Quantidade efetivamente entregue: pode ser menor que o peso indica,
            // quando o carregador corta o excedente da bobina antes de registrar
            'quantity'  => ['required', 'numeric', 'min:0.0001', 'max:999999.9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'weight_kg.required' => 'Informe o peso que deu na balança.',
            'weight_kg.numeric'  => 'Informe o peso usando apenas números.',
            'weight_kg.min'      => 'O peso precisa ser maior que zero.',
            'quantity.required'  => 'Informe a quantidade registrada.',
            'quantity.min'       => 'A quantidade precisa ser maior que zero.',
        ];
    }
}
