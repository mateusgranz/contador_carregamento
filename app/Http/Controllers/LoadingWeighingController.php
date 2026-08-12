<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWeighingRequest;
use App\Models\Loading;
use App\Models\LoadingWeighing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class LoadingWeighingController extends Controller
{
    /**
     * Registra uma pesagem no carregamento.
     */
    public function store(StoreWeighingRequest $request, Loading $carregamento): RedirectResponse
    {
        $this->autorizarAlteracao($carregamento);

        DB::transaction(function () use ($request, $carregamento) {
            $carregamento->weighings()->create([
                'weight_kg' => $request->weight_kg,
                'quantity'  => $request->quantity,
            ]);

            // Total sempre recalculado a partir das pesagens
            $carregamento->recalcularTotal();
        });

        return redirect()->route('carregamento.show', $carregamento);
    }

    /**
     * Remove uma pesagem registrada por engano.
     */
    public function destroy(Loading $carregamento, LoadingWeighing $pesagem): RedirectResponse
    {
        $this->autorizarAlteracao($carregamento);

        abort_if($pesagem->loading_id !== $carregamento->id, 403, 'Esta pesagem é de outro carregamento.');

        DB::transaction(function () use ($carregamento, $pesagem) {
            $pesagem->delete();

            $carregamento->recalcularTotal();
        });

        return redirect()->route('carregamento.show', $carregamento);
    }

    /**
     * Só o dono altera, apenas enquanto aberto e apenas em produto do modo peso.
     */
    private function autorizarAlteracao(Loading $carregamento): void
    {
        abort_if($carregamento->user_id !== auth()->id(), 403, 'Este carregamento é de outro carregador.');
        abort_unless($carregamento->emAndamento(), 403, 'Carregamento já finalizado.');
        abort_unless($carregamento->product->usaPeso(), 403, 'Este produto não é carregado por peso.');
    }
}
