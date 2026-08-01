<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * No modo peso o carregamento não tem m²: loaded_sqm precisa aceitar null,
     * assim como target_sqm já aceitava.
     */
    public function up(): void
    {
        Schema::table('loadings', function (Blueprint $table) {
            $table->decimal('loaded_sqm', 8, 4)->nullable()->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('loadings', function (Blueprint $table) {
            $table->decimal('loaded_sqm', 8, 4)->default(0)->change();
        });
    }
};
