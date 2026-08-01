<?php

namespace App\Http\Requests;

use App\Models\LoadingField;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

class StoreLoadingRequest extends FormRequest
{
    /**
     * Campos extras ativos, carregados uma única vez por requisição.
     *
     * @var Collection<int, LoadingField>|null
     */
    private ?Collection $camposAtivos = null;

    private ?Product $produto = null;

    public function authorize(): bool
    {
        return $this->user()?->role === 'carregador';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $regras = [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            // Quantidade do pedido: m² no modo pacote, metros/barras/peças no modo peso
            'quantidade' => ['required', 'numeric', 'min:0.0001', 'max:999999.9999'],
        ];

        // Cada campo ativo vira uma regra própria, conforme tipo e obrigatoriedade
        foreach ($this->camposAtivos() as $campo) {
            $regras["campos.{$campo->id}"] = $campo->regrasDeValidacao();
        }

        return $regras;
    }

    /**
     * Produto escolhido no passo 1.
     */
    public function produto(): ?Product
    {
        return $this->produto ??= Product::find($this->input('product_id'));
    }

    /**
     * Campos extras que o gestor deixou ativos.
     *
     * @return Collection<int, LoadingField>
     */
    public function camposAtivos(): Collection
    {
        return $this->camposAtivos ??= LoadingField::ativos()->get();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $unidade = $this->produto()?->unidadeLabel() ?? 'unidades';

        $mensagens = [
            'product_id.required' => 'Selecione um produto para iniciar o carregamento.',
            'product_id.exists'   => 'Produto não encontrado.',
            'quantidade.required' => "Informe quantos {$unidade} você vai carregar.",
            'quantidade.numeric'  => 'Informe a quantidade usando apenas números.',
            'quantidade.min'      => 'A quantidade precisa ser maior que zero.',
        ];

        // Sem isso os campos extras herdariam as mensagens padrão do Laravel, em inglês
        foreach ($this->camposAtivos() as $campo) {
            $chave = "campos.{$campo->id}";

            $mensagens["{$chave}.required"] = "Preencha o campo \"{$campo->label}\".";
            $mensagens["{$chave}.numeric"]  = "O campo \"{$campo->label}\" aceita apenas números.";
            $mensagens["{$chave}.date"]     = "Informe uma data válida em \"{$campo->label}\".";
            $mensagens["{$chave}.string"]   = "O campo \"{$campo->label}\" deve ser um texto.";
            $mensagens["{$chave}.max"]      = "O campo \"{$campo->label}\" é longo demais.";
        }

        return $mensagens;
    }

    /**
     * Usa o nome dado pelo gestor nas mensagens de erro dos campos extras.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $nomes = [];

        foreach ($this->camposAtivos() as $campo) {
            $nomes["campos.{$campo->id}"] = $campo->label;
        }

        return $nomes;
    }
}
