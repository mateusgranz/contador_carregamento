<?php

namespace Tests\Feature;

use App\Models\Loading;
use App\Models\PackageType;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoadingTest extends TestCase
{
    use RefreshDatabase;

    private User $carregador;
    private Product $produto;
    private PackageType $pacoteGrande;
    private PackageType $pacotePequeno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->carregador = User::factory()->create(['role' => 'carregador']);

        $this->produto = Product::create([
            'name' => 'Forro PVC',
            'unit' => 'm2',
        ]);

        // 0,20 m × 6,00 m × 8 peças = 9,6 m²
        $this->pacoteGrande = $this->produto->packageTypes()->create([
            'length_cm'    => 600,
            'width_mm'     => 200,
            'thickness_mm' => 8,
            'pieces_count' => 8,
        ]);

        // 0,20 m × 3,00 m × 8 peças = 4,8 m²
        $this->pacotePequeno = $this->produto->packageTypes()->create([
            'length_cm'    => 300,
            'width_mm'     => 200,
            'thickness_mm' => 8,
            'pieces_count' => 8,
        ]);
    }

    public function test_sqm_por_pacote_e_calculado_pelo_model(): void
    {
        $this->assertEquals(9.6, (float) $this->pacoteGrande->sqm_per_package);
        $this->assertEquals(4.8, (float) $this->pacotePequeno->sqm_per_package);
    }

    public function test_sqm_por_pacote_ignora_valor_enviado_pelo_formulario(): void
    {
        $pacote = $this->produto->packageTypes()->create([
            'length_cm'       => 300,
            'width_mm'        => 200,
            'thickness_mm'    => 8,
            'pieces_count'    => 8,
            'sqm_per_package' => 999,
        ]);

        $this->assertEquals(4.8, (float) $pacote->sqm_per_package);
    }

    public function test_gestor_nao_acessa_a_tela_de_carregamento(): void
    {
        $gestor = User::factory()->create(['role' => 'gestor']);

        $this->actingAs($gestor)->get('/carregamento')->assertForbidden();
    }

    public function test_carregador_nao_acessa_as_rotas_do_gestor(): void
    {
        $this->actingAs($this->carregador)->get('/produtos')->assertForbidden();
    }

    public function test_carregador_inicia_carregamento_e_acumula_pacotes(): void
    {
        $this->actingAs($this->carregador)
            ->post('/carregamento', ['product_id' => $this->produto->id, 'quantidade' => 50])
            ->assertRedirect();

        $carregamento = Loading::first();
        $this->assertSame('em_andamento', $carregamento->status);

        // Dois pacotes grandes + um pequeno = 9,6 + 9,6 + 4,8 = 24,0 m²
        $this->actingAs($this->carregador)
            ->post("/carregamento/{$carregamento->id}/itens", ['package_type_id' => $this->pacoteGrande->id]);
        $this->actingAs($this->carregador)
            ->post("/carregamento/{$carregamento->id}/itens", ['package_type_id' => $this->pacoteGrande->id]);
        $this->actingAs($this->carregador)
            ->post("/carregamento/{$carregamento->id}/itens", ['package_type_id' => $this->pacotePequeno->id]);

        $this->assertEquals(24.0, (float) $carregamento->fresh()->loaded_amount);
        $this->assertSame(2, $carregamento->loadingItems()->where('package_type_id', $this->pacoteGrande->id)->first()->quantity);
    }

    public function test_remover_pacote_recalcula_o_total_e_apaga_item_zerado(): void
    {
        $carregamento = $this->criarCarregamento();

        $this->actingAs($this->carregador)
            ->post("/carregamento/{$carregamento->id}/itens", ['package_type_id' => $this->pacoteGrande->id]);
        $this->actingAs($this->carregador)
            ->delete("/carregamento/{$carregamento->id}/itens/{$this->pacoteGrande->id}");

        $this->assertEquals(0.0, (float) $carregamento->fresh()->loaded_amount);
        $this->assertSame(0, $carregamento->loadingItems()->count());
    }

    public function test_pacote_ideal_e_o_mais_proximo_do_restante(): void
    {
        // Meta 15 m²: após um pacote grande (9,6) restam 5,4 — o pequeno (4,8) é o ideal
        $carregamento = $this->criarCarregamento(targetSqm: 15);

        $this->actingAs($this->carregador)
            ->post("/carregamento/{$carregamento->id}/itens", ['package_type_id' => $this->pacoteGrande->id]);

        $tipos = $this->produto->packageTypes()->get();
        $ideal = $carregamento->fresh()->pacoteIdealPara($tipos);

        $this->assertSame($this->pacotePequeno->id, $ideal->id);
    }

    public function test_nao_destaca_pacote_quando_ainda_falta_mais_de_um_pacote_cheio(): void
    {
        // Meta 100 m², nada carregado: restam 100, muito acima do maior pacote
        $carregamento = $this->criarCarregamento(targetSqm: 100);

        $tipos = $this->produto->packageTypes()->get();

        $this->assertNull($carregamento->pacoteIdealPara($tipos));
    }

    public function test_sem_meta_nao_ha_pacote_destacado(): void
    {
        $carregamento = $this->criarCarregamento();

        $tipos = $this->produto->packageTypes()->get();

        $this->assertNull($carregamento->restante());
        $this->assertNull($carregamento->pacoteIdealPara($tipos));
    }

    public function test_finalizar_bloqueia_alteracoes_e_libera_o_pdf(): void
    {
        $carregamento = $this->criarCarregamento();

        $this->actingAs($this->carregador)
            ->post("/carregamento/{$carregamento->id}/itens", ['package_type_id' => $this->pacoteGrande->id]);

        $this->actingAs($this->carregador)
            ->post("/carregamento/{$carregamento->id}/finalizar")
            ->assertRedirect(route('carregamento.resumo', $carregamento));

        $carregamento->refresh();
        $this->assertSame('finalizado', $carregamento->status);
        $this->assertNotNull($carregamento->finished_at);

        // Nenhuma alteração após finalizado
        $this->actingAs($this->carregador)
            ->post("/carregamento/{$carregamento->id}/itens", ['package_type_id' => $this->pacoteGrande->id])
            ->assertForbidden();

        $resposta = $this->actingAs($this->carregador)->get("/carregamento/{$carregamento->id}/pdf");
        $resposta->assertOk();
        $this->assertSame('application/pdf', $resposta->headers->get('content-type'));
    }

    public function test_fluxo_em_tres_passos_ate_o_contador(): void
    {
        // Passo 1: escolher o produto
        $this->actingAs($this->carregador)->get('/carregamento')
            ->assertOk()
            ->assertSee('O que você vai carregar?')
            ->assertSee(route('carregamento.quantidade', $this->produto));

        // Passo 2: informar a metragem do pedido
        $this->actingAs($this->carregador)->get(route('carregamento.quantidade', $this->produto))
            ->assertOk()
            ->assertSee('Quantos metros quadrados você vai carregar?');

        // Passo 3: contador já criado com a meta informada
        $this->actingAs($this->carregador)
            ->post('/carregamento', ['product_id' => $this->produto->id, 'quantidade' => 15]);

        $carregamento = Loading::latest('id')->first();
        $this->assertEquals(15.0, (float) $carregamento->target_amount);
    }

    public function test_metragem_e_obrigatoria_para_iniciar(): void
    {
        $this->actingAs($this->carregador)
            ->post('/carregamento', ['product_id' => $this->produto->id])
            ->assertSessionHasErrors('quantidade');

        $this->assertSame(0, Loading::count());
    }

    public function test_produto_sem_pacotes_nao_abre_a_tela_de_metragem(): void
    {
        $vazio = Product::create(['name' => 'Sem pacotes', 'unit' => 'm2']);

        $this->actingAs($this->carregador)->get(route('carregamento.quantidade', $vazio))
            ->assertRedirect(route('carregamento.index'));
    }

    public function test_todas_as_telas_do_carregador_renderizam(): void
    {
        $carregamento = $this->criarCarregamento(targetSqm: 15);

        $this->actingAs($this->carregador)
            ->post("/carregamento/{$carregamento->id}/itens", ['package_type_id' => $this->pacoteGrande->id]);

        // Contador: total no topo, m² por pacote visível e aviso de fechamento
        $this->actingAs($this->carregador)->get("/carregamento/{$carregamento->id}")
            ->assertOk()
            ->assertSee('Total carregado')
            ->assertSee('9,60')
            ->assertSee('m² por pacote')
            ->assertSee('Falta pouco!')
            ->assertSee('Basta mais 1 pacote de')
            ->assertSee('Não tenho essa medida');

        $this->actingAs($this->carregador)->post("/carregamento/{$carregamento->id}/finalizar");

        $this->actingAs($this->carregador)->get("/carregamento/{$carregamento->id}/finalizar")
            ->assertOk()
            ->assertSee('Enviar no WhatsApp')
            ->assertSee('Baixar PDF');
    }

    public function test_aviso_de_fechamento_nao_aparece_enquanto_falta_muito(): void
    {
        // Meta 100 m² e nada carregado: ainda falta muito mais que um pacote
        $carregamento = $this->criarCarregamento(targetSqm: 100);

        $this->actingAs($this->carregador)->get("/carregamento/{$carregamento->id}")
            ->assertOk()
            ->assertDontSee('Falta pouco!');
    }

    public function test_aviso_sugere_pacote_mas_permite_adicionar_outro(): void
    {
        // Meta 15: após o pacote grande (9,6) restam 5,4 — sugestão é o pequeno (4,8)
        $carregamento = $this->criarCarregamento(targetSqm: 15);

        $this->actingAs($this->carregador)
            ->post("/carregamento/{$carregamento->id}/itens", ['package_type_id' => $this->pacoteGrande->id]);

        $this->actingAs($this->carregador)->get("/carregamento/{$carregamento->id}")
            ->assertSee('Falta pouco!');

        // Ignora a sugestão e adiciona outro pacote grande — nada bloqueia
        $this->actingAs($this->carregador)
            ->post("/carregamento/{$carregamento->id}/itens", ['package_type_id' => $this->pacoteGrande->id])
            ->assertRedirect();

        $this->assertEquals(19.2, (float) $carregamento->fresh()->loaded_amount);
    }

    public function test_carregamento_finalizado_redireciona_do_contador_para_o_resumo(): void
    {
        $carregamento = $this->criarCarregamento();
        $carregamento->update(['status' => 'finalizado', 'finished_at' => now()]);

        $this->actingAs($this->carregador)->get("/carregamento/{$carregamento->id}")
            ->assertRedirect(route('carregamento.resumo', $carregamento));
    }

    public function test_dashboard_leva_cada_perfil_para_a_sua_area(): void
    {
        $this->actingAs($this->carregador)->get('/dashboard')
            ->assertRedirect(route('carregamento.index'));

        $gestor = User::factory()->create(['role' => 'gestor']);
        $this->actingAs($gestor)->get('/dashboard')
            ->assertRedirect(route('produtos.index'));
    }

    public function test_carregador_nao_enxerga_carregamento_de_outro(): void
    {
        $carregamento = $this->criarCarregamento();
        $outro = User::factory()->create(['role' => 'carregador']);

        $this->actingAs($outro)->get("/carregamento/{$carregamento->id}")->assertForbidden();
    }

    public function test_pacote_de_outro_produto_e_rejeitado(): void
    {
        $carregamento = $this->criarCarregamento();

        $outroProduto = Product::create(['name' => 'Deck', 'unit' => 'm2']);
        $pacoteAlheio = $outroProduto->packageTypes()->create([
            'length_cm'    => 200,
            'width_mm'     => 90,
            'thickness_mm' => 21,
            'pieces_count' => 20,
        ]);

        $this->actingAs($this->carregador)
            ->post("/carregamento/{$carregamento->id}/itens", ['package_type_id' => $pacoteAlheio->id])
            ->assertSessionHasErrors('package_type_id');

        $this->assertEquals(0.0, (float) $carregamento->fresh()->loaded_amount);
    }

    private function criarCarregamento(?float $targetSqm = null): Loading
    {
        return Loading::create([
            'user_id'    => $this->carregador->id,
            'product_id' => $this->produto->id,
            'target_amount' => $targetSqm,
            'loaded_amount' => 0,
            'status'     => 'em_andamento',
        ]);
    }
}
