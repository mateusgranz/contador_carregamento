<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cria a tabela de tipos de pacote.
     * sqm_per_package é calculado automaticamente pelo Model — nunca confiar no valor do formulário.
     */
    public function up(): void
    {
        Schema::create('package_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('length_cm', 8, 2);
            $table->decimal('width_mm', 8, 2);
            $table->decimal('thickness_mm', 8, 2);
            $table->integer('pieces_count');
            $table->decimal('sqm_per_package', 8, 4);
            $table->timestamps();
        });
    }

    /**
     * Remove a tabela de tipos de pacote.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_types');
    }
};
