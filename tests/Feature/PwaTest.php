<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O sistema é usado como aplicativo pela tela de início do celular.
 * Sem o manifest e as meta tags, ele abre com a barra do navegador
 * ocupando espaço e sem ícone próprio.
 */
class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_existe_e_tem_o_necessario(): void
    {
        $caminho = public_path('manifest.json');

        $this->assertFileExists($caminho, 'O manifest.json não está em public/.');

        $manifest = json_decode(file_get_contents($caminho), true);

        $this->assertIsArray($manifest, 'O manifest.json não é um JSON válido.');

        foreach (['name', 'short_name', 'start_url', 'scope', 'display', 'icons'] as $chave) {
            $this->assertArrayHasKey($chave, $manifest, "Falta \"{$chave}\" no manifest.");
        }

        // standalone é o que faz abrir sem a barra do navegador
        $this->assertSame('standalone', $manifest['display']);

        $tamanhos = array_column($manifest['icons'], 'sizes');
        $this->assertContains('192x192', $tamanhos, 'Falta o ícone de 192px.');
        $this->assertContains('512x512', $tamanhos, 'Falta o ícone de 512px.');

        $proposito = array_column($manifest['icons'], 'purpose');
        $this->assertContains('maskable', $proposito, 'Falta um ícone maskable (Android recorta o ícone).');
    }

    public function test_os_arquivos_de_icone_existem_e_sao_png(): void
    {
        foreach ($this->arquivosDeIcone() as $arquivo) {
            $caminho = public_path($arquivo);

            $this->assertFileExists($caminho, "Ícone ausente: {$arquivo}");

            $info = getimagesize($caminho);
            $this->assertSame('image/png', $info['mime'], "{$arquivo} não é PNG.");
        }
    }

    public function test_icones_tem_as_dimensoes_declaradas(): void
    {
        $esperado = [
            'icons/icon-192.png'          => 192,
            'icons/icon-512.png'          => 512,
            'icons/icon-maskable-512.png' => 512,
            'icons/apple-touch-icon.png'  => 180,
        ];

        foreach ($esperado as $arquivo => $lado) {
            [$largura, $altura] = getimagesize(public_path($arquivo));

            $this->assertSame($lado, $largura, "{$arquivo} deveria ter {$lado}px de largura.");
            $this->assertSame($lado, $altura, "{$arquivo} deveria ter {$lado}px de altura.");
        }
    }

    public function test_as_tres_telas_declaram_o_manifest(): void
    {
        $carregador = User::factory()->create(['role' => 'carregador']);
        $gestor     = User::factory()->gestor()->create();

        Product::create(['name' => 'Forro', 'unit' => 'm2', 'calc_mode' => 'pacote']);

        $paginas = [
            'login (visitante)'   => $this->get('/login'),
            'gestor'              => $this->actingAs($gestor)->get('/produtos'),
            'carregador'          => $this->actingAs($carregador)->get('/carregamento'),
        ];

        foreach ($paginas as $qual => $resposta) {
            $html = $resposta->assertOk()->getContent();

            $this->assertStringContainsString('rel="manifest"', $html, "A tela do {$qual} não declara o manifest.");
            $this->assertStringContainsString('apple-mobile-web-app-capable', $html, "A tela do {$qual} não tem a meta do iOS.");
            $this->assertStringContainsString('apple-touch-icon', $html, "A tela do {$qual} não tem o ícone do iOS.");
            $this->assertStringContainsString('name="theme-color"', $html, "A tela do {$qual} não define a cor da barra.");
        }
    }

    /**
     * @return array<int, string>
     */
    private function arquivosDeIcone(): array
    {
        return [
            'icons/icon-192.png',
            'icons/icon-512.png',
            'icons/icon-maskable-512.png',
            'icons/apple-touch-icon.png',
        ];
    }
}
