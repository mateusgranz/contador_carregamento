<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cria a tabela de produtos.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('unit', ['m2', 'm3']);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Remove a tabela de produtos.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
