<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoadingItemRequest;
use App\Models\Loading;
use App\Models\PackageType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class LoadingItemController extends Controller
{
    /**
     * Adiciona um pacote do tipo informado ao carregamento.
     */
    public function store(StoreLoadingItemRequest $request, Loading $carregamento): RedirectResponse
    {
        $this->autorizarAlteracao($carregamento);

        DB::transaction(function () use ($request, $carregamento) {
            $item = $carregamento->loadingItems()
                ->firstOrNew(['package_type_id' => $request->package_type_id]);

            $item->quantity = ($item->quantity ?? 0) + 1;
            // subtotal_sqm é calculado no Model (evento saving)
            $item->save();

            // Total sempre recalculado a partir dos itens, nunca somado incrementalmente
            $carregamento->recalcularTotal();
        });

        // Volta ancorado no pacote tocado para não perder a posição da rolagem
        return back()->withFragment("tipo-{$request->package_type_id}");
    }

    /**
     * Remove um pacote do tipo informado do carregamento.
     */
    public function destroy(Loading $carregamento, PackageType $pacote): RedirectResponse
    {
        $this->autorizarAlteracao($carregamento);

        DB::transaction(function () use ($carregamento, $pacote) {
            $item = $carregamento->loadingItems()
                ->where('package_type_id', $pacote->id)
                ->first();

            if (! $item) {
                return;
            }

            if ($item->quantity <= 1) {
                $item->delete();
            } else {
                $item->quantity -= 1;
                $item->save();
            }

            $carregamento->recalcularTotal();
        });

        return back()->withFragment("tipo-{$pacote->id}");
    }

    /**
     * Só o dono pode alterar, e apenas enquanto o carregamento está aberto.
     */
    private function autorizarAlteracao(Loading $carregamento): void
    {
        abort_if($carregamento->user_id !== auth()->id(), 403, 'Este carregamento é de outro carregador.');
        abort_unless($carregamento->emAndamento(), 403, 'Carregamento já finalizado.');
    }
}
