<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cada pesagem feita pelo carregador no modo peso.
     * loaded_qty em loadings é sempre recalculado a partir daqui — nunca somado incrementalmente.
     */
    public function up(): void
    {
        Schema::create('loading_weighings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loading_id')->constrained('loadings')->cascadeOnDelete();
            $table->decimal('weight_kg', 10, 4);
            // Quantidade entregue na unidade do produto (metros, barras ou peças)
            $table->decimal('quantity', 10, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loading_weighings');
    }
};
