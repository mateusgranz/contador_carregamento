<?php

namespace Tests\Feature;

use App\Models\PackageType;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductEditTest extends TestCase
{
    use RefreshDatabase;

    private User $gestor;
    private Product $produto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gestor = User::factory()->gestor()->create();

        $this->produto = Product::create([
            'name' => 'Forro PVC',
            'unit' => 'm2',
        ]);

        $this->produto->packageTypes()->create([
            'length_cm'    => 300,
            'width_mm'     => 200,
            'thickness_mm' => 8,
            'pieces_count' => 8,
        ]);
    }

    /**
     * Um <form> dentro de outro é HTML inválido: o navegador descarta o form
     * interno e joga os inputs dele (inclusive _method=DELETE) no form externo,
     * fazendo "Salvar Alterações" excluir o produto.
     */
    public function test_formulario_de_edicao_nao_contem_form_aninhado(): void
    {
        $html = $this->actingAs($this->gestor)
            ->get(route('produtos.edit', $this->produto))
            ->assertOk()
            ->getContent();

        $inicio = strpos($html, route('produtos.update', $this->produto));
        $this->assertNotFalse($inicio, 'Formulário de edição não encontrado na página.');

        // Vai do início do form de update até o primeiro </form> encontrado
        $trecho = substr($html, $inicio, strpos($html, '</form>', $inicio) - $inicio);

        $this->assertStringNotContainsString('<form', $trecho, 'Existe um <form> aninhado dentro do formulário de edição.');
        $this->assertStringNotContainsString('value="DELETE"', $trecho, 'O formulário de edição carrega um _method=DELETE.');
    }

    public function test_salvar_alteracoes_atualiza_o_produto_sem_excluir(): void
    {
        $this->actingAs($this->gestor)
            ->patch(route('produtos.update', $this->produto), [
                'name'      => 'Forro PVC Branco',
                'unit'      => 'm2',
                'calc_mode' => 'pacote',
            ])
            ->assertRedirect(route('produtos.edit', $this->produto));

        $this->assertDatabaseHas('products', [
            'id'   => $this->produto->id,
            'name' => 'Forro PVC Branco',
        ]);
    }

    public function test_salvar_varios_tipos_de_pacote_de_uma_vez(): void
    {
        $this->actingAs($this->gestor)
            ->patch(route('produtos.update', $this->produto), [
                'name'      => 'Forro PVC',
                'unit'      => 'm2',
                'calc_mode' => 'pacote',
                'pacotes'   => [
                    ['length_cm' => 400, 'width_mm' => 200, 'thickness_mm' => 8, 'pieces_count' => 8],
                    ['length_cm' => 600, 'width_mm' => 200, 'thickness_mm' => 8, 'pieces_count' => 8],
                ],
            ])
            ->assertRedirect(route('produtos.edit', $this->produto));

        $this->assertDatabaseHas('products', ['id' => $this->produto->id]);
        $this->assertSame(3, $this->produto->packageTypes()->count());

        // 0,20 m × 4,00 m × 8 peças = 6,4 m² | 0,20 m × 6,00 m × 8 peças = 9,6 m²
        $this->assertEqualsWithDelta(
            6.4,
            (float) PackageType::where('length_cm', 400)->first()->sqm_per_package,
            0.0001,
        );
    }

    public function test_remover_tipo_de_pacote_nao_afeta_o_produto(): void
    {
        $pacote = $this->produto->packageTypes()->first();

        $this->actingAs($this->gestor)
            ->delete(route('pacotes.destroy', [$this->produto, $pacote]))
            ->assertRedirect(route('produtos.edit', $this->produto));

        $this->assertDatabaseHas('products', ['id' => $this->produto->id]);
        $this->assertDatabaseMissing('package_types', ['id' => $pacote->id]);
    }
}
