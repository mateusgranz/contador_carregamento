<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoadingWeighing extends Model
{
    protected $fillable = [
        'loading_id',
        'weight_kg',
        'quantity',
    ];

    protected $casts = [
        'weight_kg' => 'decimal:4',
        'quantity'  => 'decimal:4',
    ];

    /**
     * Carregamento ao qual esta pesagem pertence.
     */
    public function loading(): BelongsTo
    {
        return $this->belongsTo(Loading::class);
    }
}
