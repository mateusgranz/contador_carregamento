<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Amplia as unidades de venda e unifica weight_unit dentro de unit.
     *
     * Ter dois campos de unidade (unit e weight_unit) confundia o gestor:
     * agora o produto tem uma unidade só, e no modo peso o kg_per_unit
     * se refere a ela.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('unit', ['m2', 'm3', 'm', 'br', 'cx', 'un', 'pc'])->default('m2')->change();
        });

        // Traz a unidade do modo peso para o campo unificado
        DB::table('products')->where('calc_mode', 'peso')->whereNotNull('weight_unit')->update([
            'unit' => DB::raw("CASE weight_unit
                WHEN 'metro' THEN 'm'
                WHEN 'barra' THEN 'br'
                WHEN 'peca'  THEN 'pc'
                ELSE unit END"),
        ]);

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('weight_unit');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('weight_unit', ['metro', 'barra', 'peca'])->nullable()->after('calc_mode');
        });

        DB::table('products')->where('calc_mode', 'peso')->update([
            'weight_unit' => DB::raw("CASE unit
                WHEN 'm'  THEN 'metro'
                WHEN 'br' THEN 'barra'
                WHEN 'pc' THEN 'peca'
                ELSE 'metro' END"),
        ]);

        DB::table('products')->whereNotIn('unit', ['m2', 'm3'])->update(['unit' => 'm2']);

        Schema::table('products', function (Blueprint $table) {
            $table->enum('unit', ['m2', 'm3'])->change();
        });
    }
};
