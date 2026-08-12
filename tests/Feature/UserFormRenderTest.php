<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O formulário de usuário precisa realmente renderizar os campos.
 *
 * Diretiva Blade dentro da tag de um componente (<x-text-input @if(...) ...>)
 * quebra a análise dos atributos e o input some da página, deixando só o
 * rótulo e o texto de ajuda — sem erro nenhum no log.
 */
class UserFormRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $gestor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gestor = User::factory()->gestor()->create(['code' => 'chefe']);
    }

    public function test_formulario_de_criacao_tem_todos_os_campos(): void
    {
        $html = $this->actingAs($this->gestor)->get('/usuarios/criar')->assertOk()->getContent();

        foreach (['code', 'name', 'password', 'role'] as $campo) {
            $this->assertMatchesRegularExpression(
                '/<(input|select)[^>]*name="'.$campo.'"/',
                $html,
                "O campo \"{$campo}\" não foi renderizado no formulário de criação.",
            );
        }
    }

    public function test_formulario_de_edicao_tem_todos_os_campos(): void
    {
        $usuario = User::factory()->create(['code' => 'joao', 'role' => 'carregador']);

        $html = $this->actingAs($this->gestor)
            ->get(route('usuarios.edit', $usuario))
            ->assertOk()
            ->getContent();

        foreach (['code', 'name', 'password', 'role'] as $campo) {
            $this->assertMatchesRegularExpression(
                '/<(input|select)[^>]*name="'.$campo.'"/',
                $html,
                "O campo \"{$campo}\" não foi renderizado no formulário de edição.",
            );
        }
    }

    public function test_senha_e_obrigatoria_ao_criar_e_opcional_ao_editar(): void
    {
        $criar = $this->actingAs($this->gestor)->get('/usuarios/criar')->getContent();

        preg_match('/<input[^>]*name="password"[^>]*>/', $criar, $m);
        $this->assertStringContainsString('required', $m[0], 'Ao criar, a senha deveria ser obrigatória.');

        $usuario = User::factory()->create(['code' => 'joao']);
        $editar  = $this->actingAs($this->gestor)->get(route('usuarios.edit', $usuario))->getContent();

        preg_match('/<input[^>]*name="password"[^>]*>/', $editar, $m);
        $this->assertStringNotContainsString('required', $m[0], 'Ao editar, a senha em branco mantém a atual.');
    }

    /**
     * Nenhum formulário do gestor pode ter diretiva Blade dentro de tag de componente.
     */
    public function test_nenhuma_diretiva_blade_dentro_de_tag_de_componente(): void
    {
        $raiz = str_replace('\\', '/', resource_path('views'));

        // RecursiveDirectoryIterator em vez de glob: o ** do glob não recursa,
        // e no Windows os separadores misturados faziam devolver lista vazia,
        // deixando este teste passar sem varrer nada
        $arquivos = new \RegexIterator(
            new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($raiz)),
            '/\.blade\.php$/',
        );

        $quebrados  = [];
        $conferidos = 0;

        foreach ($arquivos as $arquivo) {
            $conferidos++;
            $conteudo = file_get_contents($arquivo->getPathname());

            // Procura <x-algo ... @if/@foreach/@endif ... > dentro da mesma tag
            if (preg_match('/<x-[\w.-]+[^>]*@(if|endif|foreach|endforeach|else)\b[^>]*>/s', $conteudo)) {
                $quebrados[] = str_replace($raiz, '', str_replace('\\', '/', $arquivo->getPathname()));
            }
        }

        $this->assertGreaterThan(20, $conferidos, 'A varredura não encontrou as views — o teste passaria à toa.');
        $this->assertSame([], $quebrados, "Diretiva Blade dentro de tag de componente:\n".implode("\n", $quebrados));
    }
}
