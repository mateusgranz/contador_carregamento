<?php

namespace Tests\Feature;

use App\Models\Loading;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeightLoadingTest extends TestCase
{
    use RefreshDatabase;

    private User $gestor;
    private User $carregador;
    private Product $bobina;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gestor     = User::factory()->gestor()->create();
        $this->carregador = User::factory()->create(['role' => 'carregador']);

        // Zinco: cada metro linear pesa 1,02 kg
        $this->bobina = Product::create([
            'name'        => 'Bobina de Zinco',
            'unit'        => 'm',
            'calc_mode'   => 'peso',
            'kg_per_unit' => 1.02,
        ]);
    }

    // ---------- Conversão ----------

    public function test_converte_kg_para_metros(): void
    {
        // 35 kg ÷ 1,02 = 34,31 m
        $this->assertEqualsWithDelta(34.31, $this->bobina->kgParaUnidade(35), 0.01);
        // 30 m × 1,02 = 30,60 kg
        $this->assertEqualsWithDelta(30.60, $this->bobina->unidadeParaKg(30), 0.01);
    }

    public function test_exemplo_da_madeireira_um_kg_por_metro(): void
    {
        $zinco = Product::create([
            'name'        => 'Zinco 100cm',
            'unit'        => 'm',
            'calc_mode'   => 'peso',
            'kg_per_unit' => 1,
        ]);

        // Precisa de 30 m, a bobina pesou 35 kg = 35 m: sobram 5 m
        $this->assertEqualsWithDelta(35.0, $zinco->kgParaUnidade(35), 0.001);
        $this->assertEqualsWithDelta(30.0, $zinco->unidadeParaKg(30), 0.001);
    }

    public function test_unidade_discreta_arredonda_para_baixo(): void
    {
        $barras = Product::create([
            'name'        => 'Barra de Ferro',
            'unit'        => 'br',
            'calc_mode'   => 'peso',
            'kg_per_unit' => 12.5,
        ]);

        // 100 kg ÷ 12,5 = 8 barras exatas
        $this->assertSame(8.0, $barras->kgParaUnidade(100));
        // 106 kg = 8,48 barras -> não existe meia barra, então 8
        $this->assertSame(8.0, $barras->kgParaUnidade(106));
    }

    public function test_kg_per_unit_zerado_nao_divide_por_zero(): void
    {
        $quebrado = Product::create([
            'name'        => 'Sem fator',
            'unit'        => 'm',
            'calc_mode'   => 'peso',
            'kg_per_unit' => 0,
        ]);

        $this->assertSame(0.0, $quebrado->kgParaUnidade(35));
    }

    // ---------- Gestor ----------

    public function test_gestor_cria_produto_por_peso_sem_pacotes(): void
    {
        $this->actingAs($this->gestor)->post('/produtos', [
            'name'        => 'Bobina de Alumínio',
            'unit'        => 'm',
            'calc_mode'   => 'peso',
            'kg_per_unit' => 0.85,
        ])->assertRedirect(route('produtos.index'));

        $produto = Product::where('name', 'Bobina de Alumínio')->first();

        $this->assertTrue($produto->usaPeso());
        $this->assertEqualsWithDelta(0.85, (float) $produto->kg_per_unit, 0.0001);
        $this->assertSame(0, $produto->packageTypes()->count());
    }

    /**
     * O script do seletor de modalidade é renderizado antes de #secao-pacotes.
     * Sem esperar o DOM, getElementById devolve null e a seção de pacotes
     * nunca some — deixando inputs required escondidos que travam o envio.
     */
    public function test_script_da_modalidade_espera_o_dom_carregar(): void
    {
        foreach (['/produtos/criar', route('produtos.edit', $this->bobina)] as $url) {
            $html = $this->actingAs($this->gestor)->get($url)->assertOk()->getContent();

            $posScript = strpos($html, "getElementById('secao-pacotes')");
            $posSecao  = strpos($html, 'id="secao-pacotes"');

            if ($posScript !== false && $posSecao !== false && $posScript < $posSecao) {
                $this->assertStringContainsString(
                    "document.addEventListener('DOMContentLoaded'",
                    substr($html, 0, $posScript),
                    "Em {$url} o script busca #secao-pacotes antes do elemento existir, sem esperar o DOM.",
                );
            }
        }
    }

    public function test_pagina_de_criacao_traz_a_secao_de_pacotes_identificada(): void
    {
        $this->actingAs($this->gestor)->get('/produtos/criar')
            ->assertOk()
            ->assertSee('id="secao-pacotes"', false)
            ->assertSee('Modalidade de Cálculo');
    }

    public function test_modo_peso_exige_fator_de_conversao(): void
    {
        $this->actingAs($this->gestor)->post('/produtos', [
            'name'      => 'Bobina sem fator',
            'unit'      => 'm',
            'calc_mode' => 'peso',
        ])->assertSessionHasErrors('kg_per_unit');
    }

    public function test_modo_pacote_so_aceita_unidade_de_area(): void
    {
        // Caixa não faz sentido no modo pacote: a conta de pacotes gera área
        $this->actingAs($this->gestor)->post('/produtos', [
            'name'      => 'Forro em caixa',
            'unit'      => 'cx',
            'calc_mode' => 'pacote',
            'pacotes'   => [
                ['length_cm' => 300, 'width_mm' => 200, 'thickness_mm' => 8, 'pieces_count' => 8],
            ],
        ])->assertSessionHasErrors('unit');
    }

    public function test_modo_peso_aceita_as_novas_unidades(): void
    {
        foreach (['cx' => 'Caixa de Prego', 'un' => 'Cantoneira', 'pc' => 'Telha', 'br' => 'Vergalhão'] as $unidade => $nome) {
            $this->actingAs($this->gestor)->post('/produtos', [
                'name'        => $nome,
                'unit'        => $unidade,
                'calc_mode'   => 'peso',
                'kg_per_unit' => 3.5,
            ])->assertSessionHasNoErrors();

            $produto = Product::where('name', $nome)->first();
            $this->assertSame($unidade, $produto->unit);
            // Todas as novas unidades são inteiras
            $this->assertTrue($produto->unidadeDiscreta());
        }
    }

    public function test_abreviacoes_das_unidades(): void
    {
        $esperado = ['m2' => 'm²', 'm3' => 'm³', 'm' => 'M', 'br' => 'BR', 'cx' => 'CX', 'un' => 'UN', 'pc' => 'PC'];

        foreach ($esperado as $codigo => $abreviacao) {
            $produto = new Product(['unit' => $codigo]);
            $this->assertSame($abreviacao, $produto->unidadeAbreviada());
        }
    }

    public function test_modo_pacote_continua_exigindo_pacotes(): void
    {
        $this->actingAs($this->gestor)->post('/produtos', [
            'name'      => 'Forro sem pacote',
            'unit'      => 'm2',
            'calc_mode' => 'pacote',
        ])->assertSessionHasErrors('pacotes');
    }

    public function test_virar_modo_peso_remove_os_tipos_de_pacote(): void
    {
        $produto = Product::create(['name' => 'Forro PVC', 'unit' => 'm2', 'calc_mode' => 'pacote']);
        $produto->packageTypes()->create([
            'length_cm' => 300, 'width_mm' => 200, 'thickness_mm' => 8, 'pieces_count' => 8,
        ]);

        $this->actingAs($this->gestor)->patch(route('produtos.update', $produto), [
            'name'        => 'Forro PVC',
            'unit'        => 'm',
            'calc_mode'   => 'peso',
            'kg_per_unit' => 2,
        ])->assertRedirect();

        $this->assertSame(0, $produto->fresh()->packageTypes()->count());
    }

    // ---------- Carregador ----------

    public function test_produto_por_peso_aparece_na_escolha_mesmo_sem_pacotes(): void
    {
        $this->actingAs($this->carregador)->get('/carregamento')
            ->assertOk()
            ->assertSee('Bobina de Zinco')
            ->assertSee(route('carregamento.quantidade', $this->bobina));
    }

    public function test_passo_2_pergunta_na_unidade_do_produto(): void
    {
        $this->actingAs($this->carregador)
            ->get(route('carregamento.quantidade', $this->bobina))
            ->assertOk()
            ->assertSee('Quantos metros lineares você vai carregar?');
    }

    public function test_inicio_grava_a_quantidade_em_target_amount(): void
    {
        $this->actingAs($this->carregador)
            ->post('/carregamento', ['product_id' => $this->bobina->id, 'quantidade' => 30]);

        $carregamento = Loading::first();

        $this->assertEqualsWithDelta(30.0, (float) $carregamento->target_amount, 0.0001);
        $this->assertEqualsWithDelta(0.0, (float) $carregamento->loaded_amount, 0.0001);
        // A quantidade fica na unidade do produto — aqui, metros lineares
        $this->assertSame('m', $carregamento->product->unit);
    }

    public function test_tela_mostra_o_peso_alvo_antes_de_pesar(): void
    {
        $carregamento = $this->criarCarregamento(30);

        // 30 m × 1,02 = 30,60 kg
        $this->actingAs($this->carregador)->get(route('carregamento.show', $carregamento))
            ->assertOk()
            ->assertSee('30,60 kg na balança')
            ->assertSee('Quanto deu na balança?');
    }

    public function test_calculo_indica_quanto_retirar_quando_sobra(): void
    {
        $carregamento = $this->criarCarregamento(30);

        // Pesou 35 kg = 34,31 m -> sobram 4,31 m (4,40 kg)
        $this->actingAs($this->carregador)
            ->get(route('carregamento.show', $carregamento).'?peso=35')
            ->assertOk()
            ->assertSee('Retire')
            ->assertSee('4,31')
            ->assertSee('4,40 kg')
            ->assertSee('30,60 kg');
    }

    public function test_calculo_indica_quanto_falta_quando_e_pouco(): void
    {
        $carregamento = $this->criarCarregamento(30);

        // Pesou 20,40 kg = 20 m -> ainda faltam 10 m
        $this->actingAs($this->carregador)
            ->get(route('carregamento.show', $carregamento).'?peso=20.40')
            ->assertOk()
            ->assertSee('Ainda vai faltar')
            ->assertSee('10,00');
    }

    public function test_calcular_nao_grava_nada(): void
    {
        $carregamento = $this->criarCarregamento(30);

        $this->actingAs($this->carregador)->get(route('carregamento.show', $carregamento).'?peso=35');

        $this->assertSame(0, $carregamento->weighings()->count());
        $this->assertEqualsWithDelta(0.0, (float) $carregamento->fresh()->loaded_amount, 0.0001);
    }

    public function test_registrar_pesagem_acumula_a_quantidade(): void
    {
        $carregamento = $this->criarCarregamento(30);

        // Primeira bobina: 20,40 kg = 20 m
        $this->actingAs($this->carregador)->post(
            route('carregamento.pesagens.store', $carregamento),
            ['weight_kg' => 20.40, 'quantity' => 20],
        )->assertRedirect(route('carregamento.show', $carregamento));

        $this->assertEqualsWithDelta(20.0, (float) $carregamento->fresh()->loaded_amount, 0.0001);
        $this->assertEqualsWithDelta(10.0, $carregamento->fresh()->restante(), 0.0001);

        // Segunda bobina: 10,20 kg = 10 m
        $this->actingAs($this->carregador)->post(
            route('carregamento.pesagens.store', $carregamento),
            ['weight_kg' => 10.20, 'quantity' => 10],
        );

        $this->assertEqualsWithDelta(30.0, (float) $carregamento->fresh()->loaded_amount, 0.0001);
        $this->assertSame(2, $carregamento->weighings()->count());
    }

    public function test_remover_pesagem_recalcula_o_total(): void
    {
        $carregamento = $this->criarCarregamento(30);

        $this->actingAs($this->carregador)->post(
            route('carregamento.pesagens.store', $carregamento),
            ['weight_kg' => 20.40, 'quantity' => 20],
        );

        $pesagem = $carregamento->weighings()->first();

        $this->actingAs($this->carregador)
            ->delete(route('carregamento.pesagens.destroy', [$carregamento, $pesagem]))
            ->assertRedirect();

        $this->assertEqualsWithDelta(0.0, (float) $carregamento->fresh()->loaded_amount, 0.0001);
        $this->assertSame(0, $carregamento->weighings()->count());
    }

    public function test_pesagem_de_outro_carregamento_e_rejeitada(): void
    {
        $meu    = $this->criarCarregamento(30);
        $alheio = $this->criarCarregamento(30);

        $this->actingAs($this->carregador)->post(
            route('carregamento.pesagens.store', $alheio),
            ['weight_kg' => 10, 'quantity' => 10],
        );

        $pesagem = $alheio->weighings()->first();

        $this->actingAs($this->carregador)
            ->delete(route('carregamento.pesagens.destroy', [$meu, $pesagem]))
            ->assertForbidden();
    }

    public function test_produto_por_pacote_nao_aceita_pesagem(): void
    {
        $forro = Product::create(['name' => 'Forro PVC', 'unit' => 'm2', 'calc_mode' => 'pacote']);
        $forro->packageTypes()->create([
            'length_cm' => 300, 'width_mm' => 200, 'thickness_mm' => 8, 'pieces_count' => 8,
        ]);

        $carregamento = Loading::create([
            'user_id'    => $this->carregador->id,
            'product_id' => $forro->id,
            'target_amount' => 50,
            'loaded_amount' => 0,
            'status'     => 'em_andamento',
        ]);

        $this->actingAs($this->carregador)->post(
            route('carregamento.pesagens.store', $carregamento),
            ['weight_kg' => 10, 'quantity' => 10],
        )->assertForbidden();
    }

    public function test_pesagem_bloqueada_apos_finalizar(): void
    {
        $carregamento = $this->criarCarregamento(30);

        $this->actingAs($this->carregador)->post(
            route('carregamento.pesagens.store', $carregamento),
            ['weight_kg' => 30.60, 'quantity' => 30],
        );

        $this->actingAs($this->carregador)->post(route('carregamento.finalizar', $carregamento));

        $carregamento->refresh();
        $this->assertSame('finalizado', $carregamento->status);
        $this->assertEqualsWithDelta(30.0, (float) $carregamento->loaded_amount, 0.0001);

        $this->actingAs($this->carregador)->post(
            route('carregamento.pesagens.store', $carregamento),
            ['weight_kg' => 10, 'quantity' => 10],
        )->assertForbidden();
    }

    public function test_resumo_e_pdf_mostram_as_pesagens(): void
    {
        $carregamento = $this->criarCarregamento(30);

        $this->actingAs($this->carregador)->post(
            route('carregamento.pesagens.store', $carregamento),
            ['weight_kg' => 30.60, 'quantity' => 30],
        );
        $this->actingAs($this->carregador)->post(route('carregamento.finalizar', $carregamento));

        $this->actingAs($this->carregador)->get(route('carregamento.resumo', $carregamento))
            ->assertOk()
            ->assertSee('Pesagens')
            ->assertSee('30,60 kg na balança')
            ->assertSee('30,00');

        $resposta = $this->actingAs($this->carregador)->get(route('carregamento.pdf', $carregamento));
        $resposta->assertOk();
        $this->assertSame('application/pdf', $resposta->headers->get('content-type'));
    }

    private function criarCarregamento(float $metros): Loading
    {
        return Loading::create([
            'user_id'    => $this->carregador->id,
            'product_id' => $this->bobina->id,
            'target_amount' => $metros,
            'loaded_amount' => 0,
            'status'     => 'em_andamento',
        ]);
    }
}
