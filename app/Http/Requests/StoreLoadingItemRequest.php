<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreLoadingItemRequest extends FormRequest
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
            'package_type_id' => ['required', 'integer', 'exists:package_types,id'],
        ];
    }

    /**
     * Garante que o pacote pertence ao produto do carregamento.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $carregamento = $this->route('carregamento');
                $pacoteId     = $this->input('package_type_id');

                if (! $carregamento || ! $pacoteId) {
                    return;
                }

                $pertence = $carregamento->product
                    ->packageTypes()
                    ->whereKey($pacoteId)
                    ->exists();

                if (! $pertence) {
                    $validator->errors()->add(
                        'package_type_id',
                        'Este tipo de pacote não pertence ao produto deste carregamento.',
                    );
                }
            },
        ];
    }
}
