<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackageType extends Model
{
    protected $fillable = [
        'product_id',
        'length_cm',
        'width_mm',
        'thickness_mm',
        'pieces_count',
        'sqm_per_package',
        'cbm_per_package',
    ];

    protected $casts = [
        'length_cm'       => 'decimal:2',
        'width_mm'        => 'decimal:2',
        'thickness_mm'    => 'decimal:2',
        'pieces_count'    => 'integer',
        'sqm_per_package' => 'decimal:4',
        'cbm_per_package' => 'decimal:6',
    ];

    /**
     * Produto ao qual este tipo de pacote pertence.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Itens de carregamento que usam este tipo de pacote.
     */
    public function loadingItems(): HasMany
    {
        return $this->hasMany(LoadingItem::class);
    }

    /**
     * Quanto este pacote rende na unidade da modalidade informada.
     */
    public function rendimentoPara(string $calcMode): float
    {
        return $calcMode === 'volume'
            ? (float) $this->cbm_per_package
            : (float) $this->sqm_per_package;
    }

    /**
     * Calcula área e volume antes de salvar.
     * Nunca confiar nos valores vindos do formulário.
     *
     * Os dois são sempre gravados: assim trocar a modalidade do produto
     * não obriga a recalcular os pacotes já cadastrados.
     */
    protected static function booted(): void
    {
        static::saving(function (PackageType $packageType) {
            $larguraM     = $packageType->width_mm / 1000;
            $comprimentoM = $packageType->length_cm / 100;
            $espessuraM   = $packageType->thickness_mm / 1000;

            $packageType->sqm_per_package = $larguraM * $comprimentoM * $packageType->pieces_count;
            $packageType->cbm_per_package = $larguraM * $comprimentoM * $espessuraM * $packageType->pieces_count;
        });
    }
}
