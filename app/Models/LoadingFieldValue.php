<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoadingFieldValue extends Model
{
    protected $fillable = [
        'loading_id',
        'loading_field_id',
        'value',
    ];

    /**
     * Carregamento ao qual este valor pertence.
     */
    public function loading(): BelongsTo
    {
        return $this->belongsTo(Loading::class);
    }

    /**
     * Definição do campo preenchido.
     */
    public function loadingField(): BelongsTo
    {
        return $this->belongsTo(LoadingField::class);
    }

    /**
     * Valor formatado para exibição, conforme o tipo do campo.
     */
    public function valorFormatado(): string
    {
        if ($this->value === null || $this->value === '') {
            return '—';
        }

        return match ($this->loadingField?->type) {
            'data'   => \Carbon\Carbon::parse($this->value)->format('d/m/Y'),
            'numero' => rtrim(rtrim(number_format((float) $this->value, 2, ',', '.'), '0'), ','),
            default  => $this->value,
        };
    }
}
