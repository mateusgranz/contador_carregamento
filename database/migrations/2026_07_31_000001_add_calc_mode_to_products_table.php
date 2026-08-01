<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Modalidade de cálculo do produto.
     *
     * - pacote: conta pacotes e acumula m² (modo original)
     * - peso:   converte kg em metros/barras/peças usando kg_per_unit
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('calc_mode', ['pacote', 'peso'])->default('pacote')->after('unit');
            $table->enum('weight_unit', ['metro', 'barra', 'peca'])->nullable()->after('calc_mode');
            $table->decimal('kg_per_unit', 10, 4)->nullable()->after('weight_unit');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['calc_mode', 'weight_unit', 'kg_per_unit']);
        });
    }
};
