<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoadingItem extends Model
{
    protected $fillable = [
        'loading_id',
        'package_type_id',
        'quantity',
        'subtotal_sqm',
    ];

    protected $casts = [
        'quantity'     => 'integer',
        'subtotal_sqm' => 'decimal:4',
    ];

    /**
     * Carregamento ao qual este item pertence.
     */
    public function loading(): BelongsTo
    {
        return $this->belongsTo(Loading::class);
    }

    /**
     * Tipo de pacote deste item.
     */
    public function packageType(): BelongsTo
    {
        return $this->belongsTo(PackageType::class);
    }

    /**
     * Calcula e preenche subtotal_sqm antes de salvar.
     * subtotal_sqm = sqm_per_package × quantity
     */
    protected static function booted(): void
    {
        static::saving(function (LoadingItem $item) {
            $item->subtotal_sqm = $item->packageType->sqm_per_package * $item->quantity;
        });
    }
}
