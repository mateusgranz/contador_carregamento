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
        'target_amount',
        'loaded_amount',
        'status',
        'finished_at',
    ];

    protected $casts = [
        'target_amount' => 'decimal:4',
        'loaded_amount' => 'decimal:4',
        'finished_at'   => 'datetime',
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
     * Recalcula o total a partir dos registros, conforme a modalidade.
     * Nunca somar incrementalmente — sempre refazer a conta pela origem.
     */
    public function recalcularTotal(): void
    {
        $this->loaded_amount = $this->product->usaPeso()
            ? $this->weighings()->sum('quantity')
            : $this->loadingItems()->sum('subtotal');

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
     * Quanto ainda falta para atingir o pedido, na unidade do produto.
     */
    public function restante(): ?float
    {
        if ($this->target_amount === null) {
            return null;
        }

        return (float) $this->target_amount - (float) $this->loaded_amount;
    }

    /**
     * Determina o tipo de pacote ideal para fechar o carregamento.
     *
     * Só entra em ação quando falta menos de um pacote equivalente — ou seja,
     * quando o restante é menor que o maior pacote disponível. Entre os tipos,
     * escolhe aquele cuja medida mais se aproxima do restante.
     *
     * @param  \Illuminate\Support\Collection<int, PackageType>  $tipos
     */
    public function pacoteIdealPara($tipos): ?PackageType
    {
        $restante = $this->restante();

        if ($restante === null || $restante <= 0) {
            return null;
        }

        $modo = $this->product->calc_mode;

        $candidatos = $tipos->filter(fn (PackageType $tipo) => $tipo->rendimentoPara($modo) > 0);

        if ($candidatos->isEmpty()) {
            return null;
        }

        $maiorPacote = $candidatos->max(fn (PackageType $tipo) => $tipo->rendimentoPara($modo));

        // Ainda falta mais de um pacote cheio: nada a destacar
        if ($restante >= $maiorPacote) {
            return null;
        }

        return $candidatos
            ->sortBy(fn (PackageType $tipo) => abs($tipo->rendimentoPara($modo) - $restante))
            ->first();
    }
}
