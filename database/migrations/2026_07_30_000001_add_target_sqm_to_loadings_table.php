<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona a meta opcional de metragem ao carregamento.
     *
     * Necessária apenas para a regra de destaque do "pacote ideal para fechar":
     * sem uma meta não existe metragem restante para comparar. O campo é
     * nullable — o carregador não é obrigado a informar nada.
     */
    public function up(): void
    {
        Schema::table('loadings', function (Blueprint $table) {
            $table->decimal('target_sqm', 8, 4)->nullable()->after('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('loadings', function (Blueprint $table) {
            $table->dropColumn('target_sqm');
        });
    }
};
