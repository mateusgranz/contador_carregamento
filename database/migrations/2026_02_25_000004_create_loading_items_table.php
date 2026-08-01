<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cria a tabela de itens de carregamento.
     * subtotal_sqm = sqm_per_package × quantity
     */
    public function up(): void
    {
        Schema::create('loading_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loading_id')->constrained('loadings')->cascadeOnDelete();
            $table->foreignId('package_type_id')->constrained('package_types')->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('subtotal_sqm', 8, 4);
            $table->timestamps();
        });
    }

    /**
     * Remove a tabela de itens de carregamento.
     */
    public function down(): void
    {
        Schema::dropIfExists('loading_items');
    }
};
