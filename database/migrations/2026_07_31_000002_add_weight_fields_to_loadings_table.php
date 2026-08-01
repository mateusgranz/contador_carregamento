<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Quantidade pedida e acumulada no modo peso, na unidade do produto
     * (metros, barras ou peças). O modo pacote continua usando target_sqm/loaded_sqm.
     */
    public function up(): void
    {
        Schema::table('loadings', function (Blueprint $table) {
            $table->decimal('target_qty', 10, 4)->nullable()->after('target_sqm');
            $table->decimal('loaded_qty', 10, 4)->nullable()->after('loaded_sqm');
        });
    }

    public function down(): void
    {
        Schema::table('loadings', function (Blueprint $table) {
            $table->dropColumn(['target_qty', 'loaded_qty']);
        });
    }
};
