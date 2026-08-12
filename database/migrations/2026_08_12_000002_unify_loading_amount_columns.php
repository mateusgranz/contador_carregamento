<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unifica as colunas de quantidade do carregamento.
     *
     * Eram dois pares (target_sqm/loaded_sqm para pacote, target_qty/loaded_qty
     * para peso). Com a terceira modalidade viriam seis colunas dizendo a mesma
     * coisa em unidades diferentes. Agora é um par só: a quantidade na unidade
     * do produto, que a modalidade e o unit já definem.
     */
    public function up(): void
    {
        Schema::table('loadings', function (Blueprint $table) {
            $table->decimal('target_amount', 14, 4)->nullable()->after('product_id');
            $table->decimal('loaded_amount', 14, 4)->nullable()->after('target_amount');
        });

        // Só uma das duas origens está preenchida em cada carregamento
        DB::table('loadings')->update([
            'target_amount' => DB::raw('COALESCE(target_sqm, target_qty)'),
            'loaded_amount' => DB::raw('COALESCE(loaded_sqm, loaded_qty, 0)'),
        ]);

        Schema::table('loadings', function (Blueprint $table) {
            $table->dropColumn(['target_sqm', 'loaded_sqm', 'target_qty', 'loaded_qty']);
        });

        Schema::table('loading_items', function (Blueprint $table) {
            $table->decimal('subtotal', 14, 4)->default(0)->after('quantity');
        });

        DB::table('loading_items')->update(['subtotal' => DB::raw('subtotal_sqm')]);

        Schema::table('loading_items', function (Blueprint $table) {
            $table->dropColumn('subtotal_sqm');
        });
    }

    public function down(): void
    {
        Schema::table('loadings', function (Blueprint $table) {
            $table->decimal('target_sqm', 8, 4)->nullable();
            $table->decimal('loaded_sqm', 8, 4)->nullable()->default(0);
            $table->decimal('target_qty', 10, 4)->nullable();
            $table->decimal('loaded_qty', 10, 4)->nullable();
        });

        Schema::table('loading_items', function (Blueprint $table) {
            $table->decimal('subtotal_sqm', 8, 4)->default(0);
        });

        DB::table('loading_items')->update(['subtotal_sqm' => DB::raw('subtotal')]);

        // Volta tudo para o par de m², que era o padrão do modo pacote
        DB::table('loadings')->update([
            'target_sqm' => DB::raw('target_amount'),
            'loaded_sqm' => DB::raw('loaded_amount'),
        ]);

        Schema::table('loadings', function (Blueprint $table) {
            $table->dropColumn(['target_amount', 'loaded_amount']);
        });

        Schema::table('loading_items', function (Blueprint $table) {
            $table->dropColumn('subtotal');
        });
    }
};
