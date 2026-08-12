<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /**
     * Unidades de venda disponíveis: código => [abreviação, nome singular, nome plural].
     */
    public const UNIDADES = [
        'm2' => ['m²', 'metro quadrado', 'metros quadrados'],
        'm3' => ['m³', 'metro cúbico',   'metros cúbicos'],
        'm'  => ['M',  'metro linear',   'metros lineares'],
        'br' => ['BR', 'barra',          'barras'],
        'cx' => ['CX', 'caixa',          'caixas'],
        'un' => ['UN', 'unidade',        'unidades'],
        'pc' => ['PC', 'peça',           'peças'],
    ];

    /**
     * Cada modalidade que conta pacotes produz uma unidade só:
     * a conta de área gera m², a de volume gera m³.
     */
    public const UNIDADES_POR_MODO = [
        'pacote' => ['m2'],
        'volume' => ['m3'],
    ];

    /**
     * Unidades inteiras: não existe meia caixa nem meia peça.
     */
    public const UNIDADES_DISCRETAS = ['br', 'cx', 'un', 'pc'];

    protected $fillable = [
        'name',
        'unit',
        'description',
        'calc_mode',
        'kg_per_unit',
    ];

    protected $casts = [
        'kg_per_unit' => 'decimal:4',
    ];

    /**
     * Tipos de pacote associados ao produto.
     */
    public function packageTypes(): HasMany
    {
        return $this->hasMany(PackageType::class);
    }

    /**
     * Carregamentos deste produto.
     */
    public function loadings(): HasMany
    {
        return $this->hasMany(Loading::class);
    }

    /**
     * Indica se o produto é carregado por conversão de peso.
     */
    public function usaPeso(): bool
    {
        return $this->calc_mode === 'peso';
    }

    /**
     * Indica se o produto é contado por pacotes (área ou volume).
     */
    public function contaPacotes(): bool
    {
        return ! $this->usaPeso();
    }

    /**
     * Indica se o total acumulado é volume em vez de área.
     */
    public function usaVolume(): bool
    {
        return $this->calc_mode === 'volume';
    }

    /**
     * Unidades que o gestor pode escolher na modalidade informada.
     *
     * @return array<string, string> código => rótulo do select
     */
    public static function unidadesPara(string $calcMode): array
    {
        $codigos = self::UNIDADES_POR_MODO[$calcMode] ?? array_keys(self::UNIDADES);

        $opcoes = [];

        foreach ($codigos as $codigo) {
            [$abrev, $singular] = self::UNIDADES[$codigo];
            $opcoes[$codigo]    = ucfirst($singular)." ({$abrev})";
        }

        return $opcoes;
    }

    /**
     * Unidades inteiras não admitem fração.
     */
    public function unidadeDiscreta(): bool
    {
        return in_array($this->unit, self::UNIDADES_DISCRETAS, true);
    }

    /**
     * Rótulo da unidade por extenso, no singular ou plural.
     */
    public function unidadeLabel(float $quantidade = 2): string
    {
        [, $singular, $plural] = self::UNIDADES[$this->unit] ?? self::UNIDADES['m2'];

        return $quantidade == 1 ? $singular : $plural;
    }

    /**
     * Abreviação usada junto dos números (30,00 M / 8 CX).
     */
    public function unidadeAbreviada(): string
    {
        return (self::UNIDADES[$this->unit] ?? self::UNIDADES['m2'])[0];
    }

    /**
     * Converte um peso em kg para a unidade do produto.
     * Unidades discretas são arredondadas para baixo — pedaço incompleto não conta.
     */
    public function kgParaUnidade(float $kg): float
    {
        $fator = (float) $this->kg_per_unit;

        if ($fator <= 0) {
            return 0.0;
        }

        $quantidade = $kg / $fator;

        return $this->unidadeDiscreta()
            ? floor($quantidade)
            : round($quantidade, 2);
    }

    /**
     * Converte uma quantidade na unidade do produto para kg.
     */
    public function unidadeParaKg(float $quantidade): float
    {
        return round($quantidade * (float) $this->kg_per_unit, 2);
    }
}
