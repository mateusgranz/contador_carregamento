<?php

namespace App\Http\Controllers;

use App\Models\PackageType;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;

class PackageTypeController extends Controller
{
    /**
     * Remove um tipo de pacote, garantindo que pertence ao produto informado.
     */
    public function destroy(Product $produto, PackageType $pacote): RedirectResponse
    {
        abort_if($pacote->product_id !== $produto->id, 403);

        $pacote->delete();

        return redirect()->route('produtos.edit', $produto)
            ->with('sucesso', 'Tipo de pacote removido.');
    }
}
