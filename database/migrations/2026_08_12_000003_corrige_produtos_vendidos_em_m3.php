<?php

use App\Models\Loading;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Corrige os produtos vendidos em m³ que estavam contando pacotes por área.
     *
     * Antes da modalidade de volume, um produto com unit=m3 no modo pacote
     * acumulava m² — a fórmula ignorava a espessura. Esses produtos passam
     * para o modo volume, e os carregamentos já feitos com eles são
     * recalculados para o valor certo em m³.
     */
    public function up(): void
    {
        $produtos = Product::where('calc_mode', 'pacote')->where('unit', 'm3')->get();

        if ($produtos->isEmpty()) {
            return;
        }

        DB::table('products')->whereIn('id', $produtos->pluck('id'))->update(['calc_mode' => 'volume']);

        // Os totais gravados estavam em m²; refaz a conta em m³ a partir dos pacotes
        $carregamentos = Loading::whereIn('product_id', $produtos->pluck('id'))
            ->with('loadingItems.packageType')
            ->get();

        foreach ($carregamentos as $carregamento) {
            foreach ($carregamento->loadingItems as $item) {
                DB::table('loading_items')->where('id', $item->id)->update([
                    'subtotal' => (float) $item->packageType->cbm_per_package * $item->quantity,
                ]);
            }

            DB::table('loadings')->where('id', $carregamento->id)->update([
                'loaded_amount' => DB::table('loading_items')
                    ->where('loading_id', $carregamento->id)
                    ->sum('subtotal'),
            ]);
        }
    }

    /**
     * Sem volta: reverter devolveria os produtos a uma combinação
     * (modo pacote + unidade m³) que o sistema não aceita mais.
     */
    public function down(): void
    {
        //
    }
};
