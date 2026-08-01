<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loading extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'target_sqm',
        'loaded_sqm',
        'target_qty',
        'loaded_qty',
        'status',
        'finished_at',
    ];

    protected $casts = [
        'target_sqm'  => 'decimal:4',
        'loaded_sqm'  => 'decimal:4',
        'target_qty'  => 'decimal:4',
        'loaded_qty'  => 'decimal:4',
        'finished_at' => 'datetime',
    ];

    /**
     * Carregador responsável por este carregamento.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Produto sendo carregado.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Itens (pacotes) deste carregamento.
     */
    public function loadingItems(): HasMany
    {
        return $this->hasMany(LoadingItem::class);
    }

    /**
     * Valores dos campos extras preenchidos pelo carregador.
     */
    public function fieldValues(): HasMany
    {
        return $this->hasMany(LoadingFieldValue::class);
    }

    /**
     * Pesagens feitas neste carregamento (modo peso).
     */
    public function weighings(): HasMany
    {
        return $this->hasMany(LoadingWeighing::class);
    }

    /**
     * Recalcula loaded_qty somando todas as pesagens.
     * Mesma regra do loaded_sqm: nunca somar incrementalmente.
     */
    public function recalcularQuantidade(): void
    {
        $this->loaded_qty = $this->weighings()->sum('quantity');
        $this->save();
    }

    /**
     * Quantidade que ainda falta, na unidade do produto (modo peso).
     */
    public function restanteQty(): ?float
    {
        if ($this->target_qty === null) {
            return null;
        }

        return (float) $this->target_qty - (float) $this->loaded_qty;
    }

    /**
     * Recalcula loaded_sqm somando todos os loading_items.
     * Nunca somar incrementalmente — sempre recalcular a partir dos itens.
     */
    public function recalcularSqm(): void
    {
        $this->loaded_sqm = $this->loadingItems()->sum('subtotal_sqm');
        $this->save();
    }

    /**
     * Indica se o carregamento ainda está aberto para alterações.
     */
    public function emAndamento(): bool
    {
        return $this->status === 'em_andamento';
    }

    /**
     * Metragem que ainda falta para atingir a meta.
     * Retorna null quando nenhuma meta foi informada.
     */
    public function restanteSqm(): ?float
    {
        if ($this->target_sqm === null) {
            return null;
        }

        return (float) $this->target_sqm - (float) $this->loaded_sqm;
    }

    /**
     * Determina o tipo de pacote ideal para fechar o carregamento.
     *
     * Só entra em ação quando falta menos de um pacote equivalente — ou seja,
     * quando o restante é menor que o maior pacote disponível. Entre os tipos,
     * escolhe aquele cuja metragem mais se aproxima do restante.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\PackageType>  $tipos
     */
    public function pacoteIdealPara($tipos): ?PackageType
    {
        $restante = $this->restanteSqm();

        if ($restante === null || $restante <= 0) {
            return null;
        }

        $candidatos = $tipos->filter(fn (PackageType $tipo) => (float) $tipo->sqm_per_package > 0);

        if ($candidatos->isEmpty()) {
            return null;
        }

        $maiorPacote = $candidatos->max(fn (PackageType $tipo) => (float) $tipo->sqm_per_package);

        // Ainda falta mais de um pacote cheio: nada a destacar
        if ($restante >= $maiorPacote) {
            return null;
        }

        return $candidatos
            ->sortBy(fn (PackageType $tipo) => abs((float) $tipo->sqm_per_package - $restante))
            ->first();
    }
}
