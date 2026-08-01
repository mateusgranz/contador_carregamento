<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Campos extras que o gestor pede para o carregador preencher.
     * Ex.: "Código do pedido", texto, obrigatório, ativo.
     */
    public function up(): void
    {
        Schema::create('loading_fields', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->enum('type', ['texto', 'numero', 'data'])->default('texto');
            $table->boolean('required')->default(false);
            $table->boolean('active')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loading_fields');
    }
};
