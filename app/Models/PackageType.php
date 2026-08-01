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
    ];

    protected $casts = [
        'length_cm'       => 'decimal:2',
        'width_mm'        => 'decimal:2',
        'thickness_mm'    => 'decimal:2',
        'pieces_count'    => 'integer',
        'sqm_per_package' => 'decimal:4',
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
     * Calcula e preenche sqm_per_package antes de salvar.
     * Nunca confiar no valor vindo do formulário.
     */
    protected static function booted(): void
    {
        static::saving(function (PackageType $packageType) {
            $packageType->sqm_per_package = ($packageType->width_mm / 1000)
                * ($packageType->length_cm / 100)
                * $packageType->pieces_count;
        });
    }
}
