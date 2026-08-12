<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Terceira modalidade: contagem de pacotes acumulando m³.
     *
     * O m³ por pacote fica guardado ao lado do m², e os dois são sempre
     * calculados. Assim trocar a modalidade do produto não exige recalcular
     * os pacotes, e nenhum dos dois valores mente sobre o que representa.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('calc_mode', ['pacote', 'volume', 'peso'])->default('pacote')->change();
        });

        Schema::table('package_types', function (Blueprint $table) {
            $table->decimal('cbm_per_package', 12, 6)->default(0)->after('sqm_per_package');
        });

        // Preenche o m³ dos pacotes já cadastrados:
        // (largura/1000) × (comprimento/100) × (espessura/1000) × peças
        //
        // Os divisores levam ".0" de propósito: no SQLite, 200 / 1000 com dois
        // inteiros faz divisão inteira e devolve 0, zerando o volume inteiro.
        DB::table('package_types')->update([
            'cbm_per_package' => DB::raw(
                '(width_mm / 1000.0) * (length_cm / 100.0) * (thickness_mm / 1000.0) * pieces_count'
            ),
        ]);
    }

    public function down(): void
    {
        Schema::table('package_types', function (Blueprint $table) {
            $table->dropColumn('cbm_per_package');
        });

        DB::table('products')->where('calc_mode', 'volume')->update(['calc_mode' => 'pacote']);

        Schema::table('products', function (Blueprint $table) {
            $table->enum('calc_mode', ['pacote', 'peso'])->default('pacote')->change();
        });
    }
};
