<?php

namespace Tests\Feature;

use App\Models\Loading;
use App\Models\PackageType;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VolumeLoadingTest extends TestCase
{
    use RefreshDatabase;

    private User $gestor;
    private User $carregador;
    private Product $tabua;
    private PackageType $pacoteGrande;
    private PackageType $pacotePequeno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gestor     = User::factory()->gestor()->create();
        $this->carregador = User::factory()->create(['role' => 'carregador']);

        $this->tabua = Product::create([
            'name'      => 'Tábua Pinus Bruta',
            'unit'      => 'm3',
            'calc_mode' => 'volume',
        ]);

        // 0,15 m × 3,50 m × 0,025 m × 30 peças = 0,39375 m³
        $this->pacoteGrande = $this->tabua->packageTypes()->create([
            'length_cm'    => 350,
            'width_mm'     => 150,
            'thickness_mm' => 25,
            'pieces_count' => 30,
        ]);

        // 0,15 m × 2,50 m × 0,025 m × 30 peças = 0,28125 m³
        $this->pacotePequeno = $this->tabua->packageTypes()->create([
            'length_cm'    => 250,
            'width_mm'     => 150,
            'thickness_mm' => 25,
            'pieces_count' => 30,
        ]);
    }

    // ---------- A conta ----------

    public function test_volume_por_pacote_entra_a_espessura(): void
    {
        $this->assertEqualsWithDelta(0.39375, (float) $this->pacoteGrande->cbm_per_package, 0.000001);
        $this->assertEqualsWithDelta(0.28125, (float) $this->pacotePequeno->cbm_per_package, 0.000001);
    }

    public function test_area_continua_sendo_calculada_em_paralelo(): void
    {
        // O m² segue gravado: trocar a modalidade não exige recalcular os pacotes
        $this->assertEqualsWithDelta(15.75, (float) $this->pacoteGrande->sqm_per_package, 0.0001);
        $this->assertEqualsWithDelta(11.25, (float) $this->pacotePequeno->sqm_per_package, 0.0001);
    }

    public function test_rendimento_muda_conforme_a_modalidade(): void
    {
        $this->assertEqualsWithDelta(0.39375, $this->pacoteGrande->rendimentoPara('volume'), 0.000001);
        $this->assertEqualsWithDelta(15.75, $this->pacoteGrande->rendimentoPara('pacote'), 0.0001);
    }

    public function test_volume_ignora_valor_enviado_pelo_formulario(): void
    {
        $pacote = $this->tabua->packageTypes()->create([
            'length_cm'       => 250,
            'width_mm'        => 150,
            'thickness_mm'    => 25,
            'pieces_count'    => 30,
            'cbm_per_package' => 999,
        ]);

        $this->assertEqualsWithDelta(0.28125, (float) $pacote->cbm_per_package, 0.000001);
    }

    // ---------- Gestor ----------

    public function test_gestor_cria_produto_por_volume(): void
    {
        $this->actingAs($this->gestor)->post('/produtos', [
            'name'      => 'Viga de Eucalipto',
            'unit'      => 'm3',
            'calc_mode' => 'volume',
            'pacotes'   => [
                ['length_cm' => 400, 'width_mm' => 60, 'thickness_mm' => 120, 'pieces_count' => 10],
            ],
        ])->assertRedirect(route('produtos.index'));

        $produto = Product::where('name', 'Viga de Eucalipto')->first();

        $this->assertTrue($produto->usaVolume());
        $this->assertTrue($produto->contaPacotes());
        $this->assertFalse($produto->usaPeso());

        // 0,06 m × 4,00 m × 0,12 m × 10 = 0,288 m³
        $this->assertEqualsWithDelta(
            0.288,
            (float) $produto->packageTypes()->first()->cbm_per_package,
            0.000001,
        );
    }

    public function test_modo_volume_exige_unidade_em_metro_cubico(): void
    {
        $this->actingAs($this->gestor)->post('/produtos', [
            'name'      => 'Tábua em m2',
            'unit'      => 'm2',
            'calc_mode' => 'volume',
            'pacotes'   => [
                ['length_cm' => 250, 'width_mm' => 150, 'thickness_mm' => 25, 'pieces_count' => 30],
            ],
        ])->assertSessionHasErrors('unit');
    }

    public function test_modo_area_recusa_metro_cubico(): void
    {
        $this->actingAs($this->gestor)->post('/produtos', [
            'name'      => 'Forro em m3',
            'unit'      => 'm3',
            'calc_mode' => 'pacote',
            'pacotes'   => [
                ['length_cm' => 300, 'width_mm' => 200, 'thickness_mm' => 8, 'pieces_count' => 8],
            ],
        ])->assertSessionHasErrors('unit');
    }

    public function test_modo_volume_exige_ao_menos_um_pacote(): void
    {
        $this->actingAs($this->gestor)->post('/produtos', [
            'name'      => 'Tábua sem pacote',
            'unit'      => 'm3',
            'calc_mode' => 'volume',
        ])->assertSessionHasErrors('pacotes');
    }

    public function test_tela_do_gestor_oferece_as_tres_modalidades(): void
    {
        $this->actingAs($this->gestor)->get('/produtos/criar')
            ->assertOk()
            ->assertSee('Por área (m²)')
            ->assertSee('Por volume (m³)')
            ->assertSee('Por peso');
    }

    // ---------- Carregador ----------

    public function test_passo_2_pergunta_em_metros_cubicos(): void
    {
        $this->actingAs($this->carregador)
            ->get(route('carregamento.quantidade', $this->tabua))
            ->assertOk()
            ->assertSee('Quantos metros cúbicos você vai carregar?');
    }

    public function test_contador_acumula_volume_e_nao_area(): void
    {
        $carregamento = $this->criarCarregamento(2);

        // Dois pacotes grandes = 0,39375 × 2 = 0,7875 m³
        $this->actingAs($this->carregador)
            ->post("/carregamento/{$carregamento->id}/itens", ['package_type_id' => $this->pacoteGrande->id]);
        $this->actingAs($this->carregador)
            ->post("/carregamento/{$carregamento->id}/itens", ['package_type_id' => $this->pacoteGrande->id]);

        $this->assertEqualsWithDelta(0.7875, (float) $carregamento->fresh()->loaded_amount, 0.0001);

        // E não os 31,50 m² que a conta de área daria
        $this->assertNotEqualsWithDelta(31.5, (float) $carregamento->fresh()->loaded_amount, 0.1);
    }

    public function test_tela_mostra_o_total_em_metro_cubico(): void
    {
        $carregamento = $this->criarCarregamento(2);

        $this->actingAs($this->carregador)
            ->post("/carregamento/{$carregamento->id}/itens", ['package_type_id' => $this->pacoteGrande->id]);

        $this->actingAs($this->carregador)->get(route('carregamento.show', $carregamento))
            ->assertOk()
            ->assertSee('Total carregado')
            ->assertSee('m³')
            ->assertSee('0,3938')      // total acumulado
            ->assertSee('por pacote');
    }

    public function test_aviso_de_fechamento_usa_o_volume(): void
    {
        // Pedido de 0,70 m³: após o grande (0,39375) restam 0,30625 —
        // o pequeno (0,28125) é o mais próximo
        $carregamento = $this->criarCarregamento(0.70);

        $this->actingAs($this->carregador)
            ->post("/carregamento/{$carregamento->id}/itens", ['package_type_id' => $this->pacoteGrande->id]);

        $ideal = $carregamento->fresh()->pacoteIdealPara($this->tabua->packageTypes()->get());

        $this->assertSame($this->pacotePequeno->id, $ideal->id);

        $this->actingAs($this->carregador)->get(route('carregamento.show', $carregamento))
            ->assertSee('Falta pouco!');
    }

    public function test_resumo_e_pdf_saem_em_metro_cubico(): void
    {
        $carregamento = $this->criarCarregamento(1);

        $this->actingAs($this->carregador)
            ->post("/carregamento/{$carregamento->id}/itens", ['package_type_id' => $this->pacoteGrande->id]);
        $this->actingAs($this->carregador)
            ->post(route('carregamento.finalizar', $carregamento));

        $this->actingAs($this->carregador)->get(route('carregamento.resumo', $carregamento))
            ->assertOk()
            ->assertSee('m³')
            ->assertSee('0,3938');

        $resposta = $this->actingAs($this->carregador)->get(route('carregamento.pdf', $carregamento));
        $resposta->assertOk();
        $this->assertSame('application/pdf', $resposta->headers->get('content-type'));
    }

    public function test_produto_por_volume_aparece_na_escolha(): void
    {
        $this->actingAs($this->carregador)->get('/carregamento')
            ->assertOk()
            ->assertSee('Tábua Pinus Bruta')
            ->assertSee('Por volume (m³)');
    }

    public function test_trocar_de_area_para_volume_nao_exige_recadastrar_pacotes(): void
    {
        $forro = Product::create(['name' => 'Forro PVC', 'unit' => 'm2', 'calc_mode' => 'pacote']);
        $forro->packageTypes()->create([
            'length_cm' => 300, 'width_mm' => 200, 'thickness_mm' => 8, 'pieces_count' => 8,
        ]);

        $this->actingAs($this->gestor)->patch(route('produtos.update', $forro), [
            'name'      => 'Forro PVC',
            'unit'      => 'm3',
            'calc_mode' => 'volume',
        ])->assertRedirect();

        $forro->refresh();
        $this->assertTrue($forro->usaVolume());
        // Os pacotes continuam lá, agora rendendo m³
        $this->assertSame(1, $forro->packageTypes()->count());

        // 0,20 m × 3,00 m × 0,008 m × 8 = 0,0384 m³
        $this->assertEqualsWithDelta(
            0.0384,
            (float) $forro->packageTypes()->first()->cbm_per_package,
            0.000001,
        );
    }

    private function criarCarregamento(float $metrosCubicos): Loading
    {
        return Loading::create([
            'user_id'       => $this->carregador->id,
            'product_id'    => $this->tabua->id,
            'target_amount' => $metrosCubicos,
            'loaded_amount' => 0,
            'status'        => 'em_andamento',
        ]);
    }
}
