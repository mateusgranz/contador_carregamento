<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cria a tabela de carregamentos.
     * loaded_sqm deve ser sempre recalculado a partir dos loading_items — nunca somado incrementalmente.
     */
    public function up(): void
    {
        Schema::create('loadings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('loaded_sqm', 8, 4)->default(0);
            $table->enum('status', ['em_andamento', 'finalizado'])->default('em_andamento');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Remove a tabela de carregamentos.
     */
    public function down(): void
    {
        Schema::dropIfExists('loadings');
    }
};
