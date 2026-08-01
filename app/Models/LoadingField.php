<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoadingField extends Model
{
    protected $fillable = [
        'label',
        'type',
        'required',
        'active',
        'position',
    ];

    protected $casts = [
        'required' => 'boolean',
        'active'   => 'boolean',
        'position' => 'integer',
    ];

    /**
     * Valores preenchidos para este campo.
     */
    public function values(): HasMany
    {
        return $this->hasMany(LoadingFieldValue::class);
    }

    /**
     * Apenas os campos ligados pelo toggle, na ordem definida pelo gestor.
     */
    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('active', true)->orderBy('position')->orderBy('id');
    }

    /**
     * Regras de validação do valor deste campo, conforme tipo e obrigatoriedade.
     *
     * @return array<int, string>
     */
    public function regrasDeValidacao(): array
    {
        $regras = [$this->required ? 'required' : 'nullable'];

        $regras[] = match ($this->type) {
            'numero' => 'numeric',
            'data'   => 'date',
            default  => 'string',
        };

        if ($this->type === 'texto') {
            $regras[] = 'max:255';
        }

        return $regras;
    }

    /**
     * Tipo de input HTML correspondente.
     */
    public function tipoInput(): string
    {
        return match ($this->type) {
            'numero' => 'number',
            'data'   => 'date',
            default  => 'text',
        };
    }
}
