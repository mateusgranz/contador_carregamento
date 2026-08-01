<?php

namespace Tests\Feature;

use App\Models\Loading;
use App\Models\LoadingField;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoadingFieldTest extends TestCase
{
    use RefreshDatabase;

    private User $gestor;
    private User $carregador;
    private Product $produto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gestor     = User::factory()->gestor()->create();
        $this->carregador = User::factory()->create(['role' => 'carregador']);

        $this->produto = Product::create(['name' => 'Forro PVC', 'unit' => 'm2']);
        $this->produto->packageTypes()->create([
            'length_cm'    => 300,
            'width_mm'     => 200,
            'thickness_mm' => 8,
            'pieces_count' => 8,
        ]);
    }

    // ---------- Gestor ----------

    public function test_carregador_nao_acessa_a_tela_de_campos(): void
    {
        $this->actingAs($this->carregador)->get('/campos')->assertForbidden();
    }

    public function test_gestor_cria_campo_com_nome_tipo_e_obrigatoriedade(): void
    {
        $this->actingAs($this->gestor)
            ->post('/campos', [
                'label'    => 'Código do pedido',
                'type'     => 'texto',
                'required' => '1',
            ])
            ->assertRedirect(route('campos.index'));

        $campo = LoadingField::first();

        $this->assertSame('Código do pedido', $campo->label);
        $this->assertSame('texto', $campo->type);
        $this->assertTrue($campo->required);
        // Nasce desligado: só aparece para o carregador depois do toggle
        $this->assertFalse($campo->active);
    }

    public function test_toggle_liga_e_desliga_o_campo(): void
    {
        $campo = LoadingField::create(['label' => 'Placa', 'type' => 'texto']);

        $this->actingAs($this->gestor)
            ->patch(route('campos.update', $campo), ['active' => 1])
            ->assertRedirect(route('campos.index'));
        $this->assertTrue($campo->fresh()->active);

        $this->actingAs($this->gestor)
            ->patch(route('campos.update', $campo), ['active' => 0]);
        $this->assertFalse($campo->fresh()->active);
    }

    public function test_toggle_nao_apaga_nome_tipo_nem_obrigatoriedade(): void
    {
        $campo = LoadingField::create([
            'label'    => 'Código do pedido',
            'type'     => 'numero',
            'required' => true,
        ]);

        $this->actingAs($this->gestor)->patch(route('campos.update', $campo), ['active' => 1]);

        $campo->refresh();
        $this->assertSame('Código do pedido', $campo->label);
        $this->assertSame('numero', $campo->type);
        $this->assertTrue($campo->required);
    }

    public function test_tela_de_campos_nao_tem_form_aninhado(): void
    {
        LoadingField::create(['label' => 'Placa', 'type' => 'texto', 'active' => true]);

        $html = $this->actingAs($this->gestor)->get('/campos')->assertOk()->getContent();

        preg_match_all('/<form[^>]*>|<\/form>/i', $html, $m, PREG_OFFSET_CAPTURE);

        $profundidade = 0;
        foreach ($m[0] as $tag) {
            $profundidade += str_starts_with($tag[0], '</') ? -1 : 1;
            $this->assertLessThanOrEqual(1, $profundidade, 'Existe um <form> aninhado na tela de campos.');
        }
    }

    // ---------- Carregador ----------

    public function test_campo_inativo_nao_aparece_para_o_carregador(): void
    {
        LoadingField::create(['label' => 'Código do pedido', 'type' => 'texto', 'active' => false]);

        $this->actingAs($this->carregador)
            ->get(route('carregamento.quantidade', $this->produto))
            ->assertOk()
            ->assertDontSee('Código do pedido');
    }

    public function test_campo_ativo_aparece_para_o_carregador(): void
    {
        LoadingField::create(['label' => 'Código do pedido', 'type' => 'texto', 'active' => true]);

        $this->actingAs($this->carregador)
            ->get(route('carregamento.quantidade', $this->produto))
            ->assertOk()
            ->assertSee('Código do pedido');
    }

    public function test_campo_obrigatorio_bloqueia_o_inicio_do_carregamento(): void
    {
        $campo = LoadingField::create([
            'label'    => 'Código do pedido',
            'type'     => 'texto',
            'required' => true,
            'active'   => true,
        ]);

        $this->actingAs($this->carregador)
            ->post('/carregamento', ['product_id' => $this->produto->id, 'quantidade' => 50])
            ->assertSessionHasErrors("campos.{$campo->id}");

        $this->assertSame(0, Loading::count());
    }

    public function test_mensagens_de_erro_dos_campos_saem_em_portugues(): void
    {
        $texto  = LoadingField::create(['label' => 'Código do pedido', 'type' => 'texto', 'required' => true, 'active' => true]);
        $numero = LoadingField::create(['label' => 'Nº da nota', 'type' => 'numero', 'required' => true, 'active' => true]);

        $this->actingAs($this->carregador)
            ->post('/carregamento', [
                'product_id' => $this->produto->id,
                'quantidade' => 50,
                'campos'     => [$numero->id => 'abc'],
            ])
            ->assertSessionHasErrors([
                "campos.{$texto->id}"  => 'Preencha o campo "Código do pedido".',
                "campos.{$numero->id}" => 'O campo "Nº da nota" aceita apenas números.',
            ]);
    }

    public function test_campo_opcional_nao_bloqueia(): void
    {
        LoadingField::create([
            'label'    => 'Observação',
            'type'     => 'texto',
            'required' => false,
            'active'   => true,
        ]);

        $this->actingAs($this->carregador)
            ->post('/carregamento', ['product_id' => $this->produto->id, 'quantidade' => 50])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Loading::count());
    }

    public function test_valores_preenchidos_sao_salvos_no_carregamento(): void
    {
        $codigo = LoadingField::create(['label' => 'Código do pedido', 'type' => 'texto', 'required' => true, 'active' => true]);
        $data   = LoadingField::create(['label' => 'Data de entrega', 'type' => 'data', 'active' => true]);

        $this->actingAs($this->carregador)->post('/carregamento', [
            'product_id' => $this->produto->id,
            'quantidade' => 50,
            'campos'     => [
                $codigo->id => 'PED-4471',
                $data->id   => '2026-08-15',
            ],
        ])->assertSessionHasNoErrors();

        $carregamento = Loading::first();

        $this->assertSame('PED-4471', $carregamento->fieldValues()->where('loading_field_id', $codigo->id)->first()->value);
        $this->assertSame('2026-08-15', $carregamento->fieldValues()->where('loading_field_id', $data->id)->first()->value);
    }

    public function test_campo_do_tipo_numero_recusa_texto(): void
    {
        $campo = LoadingField::create(['label' => 'Nº da nota', 'type' => 'numero', 'required' => true, 'active' => true]);

        $this->actingAs($this->carregador)->post('/carregamento', [
            'product_id' => $this->produto->id,
            'quantidade' => 50,
            'campos'     => [$campo->id => 'abc'],
        ])->assertSessionHasErrors("campos.{$campo->id}");
    }

    public function test_valores_aparecem_no_resumo_e_no_pdf(): void
    {
        $campo = LoadingField::create(['label' => 'Código do pedido', 'type' => 'texto', 'required' => true, 'active' => true]);

        $this->actingAs($this->carregador)->post('/carregamento', [
            'product_id' => $this->produto->id,
            'quantidade' => 50,
            'campos'     => [$campo->id => 'PED-4471'],
        ]);

        $carregamento = Loading::first();
        $this->actingAs($this->carregador)->post("/carregamento/{$carregamento->id}/finalizar");

        $this->actingAs($this->carregador)
            ->get(route('carregamento.resumo', $carregamento))
            ->assertOk()
            ->assertSee('Código do pedido')
            ->assertSee('PED-4471');

        $this->actingAs($this->carregador)
            ->get(route('carregamento.pdf', $carregamento))
            ->assertOk();
    }

    public function test_desativar_campo_nao_afeta_carregamentos_ja_registrados(): void
    {
        $campo = LoadingField::create(['label' => 'Código do pedido', 'type' => 'texto', 'active' => true]);

        $this->actingAs($this->carregador)->post('/carregamento', [
            'product_id' => $this->produto->id,
            'quantidade' => 50,
            'campos'     => [$campo->id => 'PED-1'],
        ]);

        $this->actingAs($this->gestor)->patch(route('campos.update', $campo), ['active' => 0]);

        $carregamento = Loading::first();
        $this->assertSame('PED-1', $carregamento->fieldValues()->first()->value);
    }
}
