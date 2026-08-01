<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $produtos = Product::withCount('packageTypes')->latest()->get();

        return view('produtos.index', compact('produtos'));
    }

    public function create(): View
    {
        return view('produtos.create');
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $produto = Product::create($this->dadosDoProduto($request));

            // sqm_per_package calculado automaticamente pelo Model (evento saving)
            foreach ($request->pacotes ?? [] as $pacote) {
                $produto->packageTypes()->create($pacote);
            }
        });

        return redirect()->route('produtos.index')
            ->with('sucesso', 'Produto criado com sucesso.');
    }

    public function edit(Product $produto): View
    {
        $produto->load('packageTypes');

        return view('produtos.edit', compact('produto'));
    }

    public function update(UpdateProductRequest $request, Product $produto): RedirectResponse
    {
        DB::transaction(function () use ($request, $produto) {
            $produto->update($this->dadosDoProduto($request));

            // Ao virar modo peso, os tipos de pacote deixam de fazer sentido
            if ($produto->usaPeso()) {
                $produto->packageTypes()->delete();

                return;
            }

            foreach ($request->pacotes ?? [] as $pacote) {
                $produto->packageTypes()->create($pacote);
            }
        });

        return redirect()->route('produtos.edit', $produto)
            ->with('sucesso', 'Produto atualizado com sucesso.');
    }

    public function destroy(Product $produto): RedirectResponse
    {
        $produto->delete();

        return redirect()->route('produtos.index')
            ->with('sucesso', 'Produto excluído com sucesso.');
    }

    /**
     * Monta os dados do produto zerando o que não pertence à modalidade escolhida.
     *
     * @return array<string, mixed>
     */
    private function dadosDoProduto(StoreProductRequest|UpdateProductRequest $request): array
    {
        $ehPeso = $request->calc_mode === 'peso';

        return [
            'name'        => $request->name,
            'unit'        => $request->unit,
            'description' => $request->description,
            'calc_mode'   => $request->calc_mode,
            'kg_per_unit' => $ehPeso ? $request->kg_per_unit : null,
        ];
    }
}
