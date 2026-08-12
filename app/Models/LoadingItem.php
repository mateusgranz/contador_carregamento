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
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'subtotal' => 'decimal:4',
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
     * Calcula e preenche o subtotal antes de salvar, na unidade da
     * modalidade do produto: m² no modo pacote, m³ no modo volume.
     */
    protected static function booted(): void
    {
        static::saving(function (LoadingItem $item) {
            $modo = $item->loading->product->calc_mode;

            $item->subtotal = $item->packageType->rendimentoPara($modo) * $item->quantity;
        });
    }
}
