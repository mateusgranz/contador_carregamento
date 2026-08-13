<?php

namespace Tests\Feature;

use App\Models\Loading;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O envio pelo WhatsApp deve levar o comprovante em PDF, não um texto.
 */
class WhatsappShareTest extends TestCase
{
    use RefreshDatabase;

    private User $carregador;
    private Loading $carregamento;

    protected function setUp(): void
    {
        parent::setUp();

        $this->carregador = User::factory()->create(['role' => 'carregador', 'name' => 'Zé Toca']);

        $produto = Product::create(['name' => 'Forro PVC Branco', 'unit' => 'm2', 'calc_mode' => 'pacote']);
        $pacote  = $produto->packageTypes()->create([
            'length_cm' => 300, 'width_mm' => 200, 'thickness_mm' => 8, 'pieces_count' => 8,
        ]);

        $this->carregamento = Loading::create([
            'user_id'       => $this->carregador->id,
            'product_id'    => $produto->id,
            'target_amount' => 10,
            'loaded_amount' => 0,
            'status'        => 'em_andamento',
        ]);

        $this->actingAs($this->carregador)->post(
            "/carregamento/{$this->carregamento->id}/itens",
            ['package_type_id' => $pacote->id],
        );
        $this->actingAs($this->carregador)->post("/carregamento/{$this->carregamento->id}/finalizar");
        $this->carregamento->refresh();
    }

    public function test_o_nome_do_arquivo_identifica_o_carregamento(): void
    {
        $nome = $this->carregamento->nomeDoArquivoPdf();

        $this->assertStringContainsString('forro-pvc-branco', $nome);
        $this->assertStringContainsString($this->carregamento->finished_at->format('d-m-Y'), $nome);
        $this->assertStringEndsWith('.pdf', $nome);
    }

    public function test_o_download_usa_esse_nome(): void
    {
        $resposta = $this->actingAs($this->carregador)
            ->get(route('carregamento.pdf', $this->carregamento))
            ->assertOk();

        $this->assertStringContainsString(
            $this->carregamento->nomeDoArquivoPdf(),
            $resposta->headers->get('content-disposition'),
        );
    }

    public function test_a_rota_devolve_um_pdf_de_verdade(): void
    {
        $resposta = $this->actingAs($this->carregador)
            ->get(route('carregamento.pdf', $this->carregamento))
            ->assertOk();

        $conteudo = $resposta->getContent();

        $this->assertSame('application/pdf', $resposta->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $conteudo);

        // Guarda contra a fonte deixar de ser embutida e o acento sumir no
        // aparelho de quem recebe
        $this->assertStringContainsString('DejaVuSans', $conteudo);
    }

    public function test_a_tela_prepara_o_pdf_e_nao_manda_texto_solto(): void
    {
        $html = $this->actingAs($this->carregador)
            ->get(route('carregamento.resumo', $this->carregamento))
            ->assertOk()
            ->getContent();

        // Busca o PDF e monta um File para o compartilhamento nativo
        $this->assertStringContainsString('navigator.canShare', $html);
        $this->assertStringContainsString("type: 'application/pdf'", $html);
        $this->assertStringContainsString('files: [arquivo]', $html);

        // A legenda é curta: o detalhamento fica no comprovante
        $this->assertStringNotContainsString('Carregamento finalizado*\n', $html);
    }

    public function test_a_legenda_traz_produto_total_e_carregador(): void
    {
        $html = $this->actingAs($this->carregador)
            ->get(route('carregamento.resumo', $this->carregamento))
            ->assertOk()
            ->getContent();

        foreach (['Forro PVC Branco', '4,80', 'm²', 'Zé Toca'] as $trecho) {
            $this->assertStringContainsString($trecho, $html, "A legenda deveria conter \"{$trecho}\".");
        }
    }

    public function test_quem_nao_e_dono_nao_baixa_o_comprovante(): void
    {
        $outro = User::factory()->create(['role' => 'carregador']);

        $this->actingAs($outro)
            ->get(route('carregamento.pdf', $this->carregamento))
            ->assertForbidden();
    }
}
