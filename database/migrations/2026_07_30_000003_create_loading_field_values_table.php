<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Valores preenchidos pelo carregador para cada campo extra.
     */
    public function up(): void
    {
        Schema::create('loading_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loading_id')->constrained('loadings')->cascadeOnDelete();
            $table->foreignId('loading_field_id')->constrained('loading_fields')->cascadeOnDelete();
            // Guardado como texto: o tipo serve para validar e exibir, não para tipar a coluna
            $table->string('value')->nullable();
            $table->timestamps();

            $table->unique(['loading_id', 'loading_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loading_field_values');
    }
};
