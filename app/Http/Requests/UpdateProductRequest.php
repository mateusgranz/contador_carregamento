<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'gestor';
    }

    public function rules(): array
    {
        $modo   = (string) $this->input('calc_mode');
        $ehPeso = $modo === 'peso';

        $unidades = implode(',', array_keys(Product::unidadesPara($modo)));

        $regras = [
            'name'        => ['required', 'string', 'max:255'],
            'unit'        => ['required', "in:{$unidades}"],
            'description' => ['nullable', 'string'],
            'calc_mode'   => ['required', 'in:pacote,volume,peso'],
        ];

        if ($ehPeso) {
            return $regras + [
                'kg_per_unit' => ['required', 'numeric', 'min:0.0001'],
            ];
        }

        return $regras + [
            // Novos pacotes são opcionais na edição
            'pacotes'                => ['nullable', 'array'],
            'pacotes.*.length_cm'    => ['required_with:pacotes', 'numeric', 'min:0.01'],
            'pacotes.*.width_mm'     => ['required_with:pacotes', 'numeric', 'min:0.01'],
            'pacotes.*.thickness_mm' => ['required_with:pacotes', 'numeric', 'min:0.01'],
            'pacotes.*.pieces_count' => ['required_with:pacotes', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'                   => 'nome',
            'unit'                   => 'unidade de venda',
            'calc_mode'              => 'modalidade de cálculo',
            'kg_per_unit'            => 'peso por unidade',
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
            'unit.in'              => $this->input('calc_mode') === 'volume'
                ? 'No modo volume a unidade precisa ser m³.'
                : 'No modo pacote a unidade precisa ser m².',
        ];
    }
}
