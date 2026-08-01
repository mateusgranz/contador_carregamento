<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'gestor';
    }

    public function rules(): array
    {
        $ehPeso = $this->input('calc_mode') === 'peso';

        // A unidade depende da modalidade: contar pacotes só faz sentido em área
        $unidades = implode(',', array_keys(Product::unidadesPara($ehPeso ? 'peso' : 'pacote')));

        $regras = [
            'name'        => ['required', 'string', 'max:255'],
            'unit'        => ['required', "in:{$unidades}"],
            'description' => ['nullable', 'string'],
            'calc_mode'   => ['required', 'in:pacote,peso'],
        ];

        // No modo peso o produto não tem tipos de pacote, e sim um fator de conversão
        if ($ehPeso) {
            return $regras + [
                'kg_per_unit' => ['required', 'numeric', 'min:0.0001'],
            ];
        }

        return $regras + [
            'pacotes'                => ['required', 'array', 'min:1'],
            'pacotes.*.length_cm'    => ['required', 'numeric', 'min:0.01'],
            'pacotes.*.width_mm'     => ['required', 'numeric', 'min:0.01'],
            'pacotes.*.thickness_mm' => ['required', 'numeric', 'min:0.01'],
            'pacotes.*.pieces_count' => ['required', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'                   => 'nome',
            'unit'                   => 'unidade de venda',
            'calc_mode'              => 'modalidade de cálculo',
            'kg_per_unit'            => 'peso por unidade',
            'pacotes'                => 'tipos de pacote',
            'pacotes.*.length_cm'    => 'comprimento',
            'pacotes.*.width_mm'     => 'largura',
            'pacotes.*.thickness_mm' => 'espessura',
            'pacotes.*.pieces_count' => 'qtd. de peças',
        ];
    }

    public function messages(): array
    {
        return [
            'kg_per_unit.required' => 'Informe quantos kg pesa cada unidade.',
            'unit.in'              => 'No modo pacote a unidade precisa ser m² ou m³.',
        ];
    }
}
