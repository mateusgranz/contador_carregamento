<?php

namespace Tests\Feature;

use App\Models\Loading;
use App\Models\LoadingField;
use App\Models\LoadingWeighing;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

/**
 * Varre TODAS as rotas registradas e garante que nenhuma fique aberta por
 * engano. Rota nova sem middleware quebra este teste automaticamente.
 */
class RouteProtectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Rotas que devem mesmo ficar abertas ao público.
     *
     * @var array<int, string>
     */
    private const PUBLICAS = [
        'GET /',            // redireciona para login
        'GET /login',
        'POST /login',
        'GET /up',          // healthcheck do container
    ];

    public function test_nenhuma_rota_fica_aberta_sem_querer(): void
    {
        $abertas = [];

        foreach ($this->rotasDaAplicacao() as [$metodo, $uri, $rota]) {
            $chave = "{$metodo} {$uri}";

            if (in_array($chave, self::PUBLICAS, true)) {
                continue;
            }

            $middleware = $rota->gatherMiddleware();

            // 'auth' protege, 'guest' é para telas de visitante (logout usa sessão)
            $protegida = in_array('auth', $middleware, true)
                || in_array('guest', $middleware, true);

            if (! $protegida) {
                $abertas[] = $chave.'  [middleware: '.implode(', ', $middleware).']';
            }
        }

        $this->assertSame([], $abertas, "Rotas sem proteção:\n".implode("\n", $abertas));
    }

    public function test_visitante_nao_alcanca_nenhuma_tela_interna(): void
    {
        $vazadas = [];

        foreach ($this->rotasDaAplicacao() as [$metodo, $uri, $rota]) {
            if (in_array("{$metodo} {$uri}", self::PUBLICAS, true) || $metodo !== 'GET') {
                continue;
            }

            // Parâmetros fictícios bastam: o middleware roda antes do model binding
            $url = preg_replace('/\{[^}]+\}/', '1', $uri);

            $resposta = $this->get($url);

            if ($resposta->getStatusCode() === 200) {
                $vazadas[] = "{$metodo} {$url} devolveu 200 para visitante";
            }
        }

        $this->assertSame([], $vazadas, implode("\n", $vazadas));
    }

    public function test_carregador_nao_alcanca_nenhuma_rota_de_gestor(): void
    {
        $carregador = User::factory()->create(['role' => 'carregador']);

        $this->assertPerfilBloqueado($carregador, 'gestor');
    }

    public function test_gestor_nao_alcanca_nenhuma_rota_de_carregador(): void
    {
        $gestor = User::factory()->gestor()->create();

        $this->assertPerfilBloqueado($gestor, 'carregador');
    }

    /**
     * Cria um registro de cada tipo para que o model binding resolva e o
     * middleware de perfil seja de fato quem recusa o acesso.
     *
     * @return array<string, int|string>
     */
    private function criarFixtures(): array
    {
        $dono = User::factory()->create(['role' => 'carregador']);

        $produto = Product::create(['name' => 'Forro PVC', 'unit' => 'm2', 'calc_mode' => 'pacote']);

        $pacote = $produto->packageTypes()->create([
            'length_cm' => 300, 'width_mm' => 200, 'thickness_mm' => 8, 'pieces_count' => 8,
        ]);

        $carregamento = Loading::create([
            'user_id'    => $dono->id,
            'product_id' => $produto->id,
            'target_sqm' => 50,
            'loaded_sqm' => 0,
            'status'     => 'em_andamento',
        ]);

        $campo = LoadingField::create(['label' => 'Código', 'type' => 'texto']);

        $pesagem = LoadingWeighing::create([
            'loading_id' => $carregamento->id, 'weight_kg' => 10, 'quantity' => 10,
        ]);

        return [
            'produto'      => $produto->id,
            'pacote'       => $pacote->id,
            'carregamento' => $carregamento->id,
            'campo'        => $campo->id,
            'pesagem'      => $pesagem->id,
            'usuario'      => $dono->id,
        ];
    }

    /**
     * Garante que o usuário recebe 403 em toda rota GET do outro perfil.
     */
    private function assertPerfilBloqueado(User $usuario, string $middlewareDoOutroPerfil): void
    {
        $ids       = $this->criarFixtures();
        $vazadas   = [];
        $conferidas = 0;

        foreach ($this->rotasDaAplicacao() as [$metodo, $uri, $rota]) {
            if ($metodo !== 'GET' || ! in_array($middlewareDoOutroPerfil, $rota->gatherMiddleware(), true)) {
                continue;
            }

            $url = preg_replace_callback(
                '/\{(\w+)\??\}/',
                fn (array $m) => (string) ($ids[$m[1]] ?? 1),
                $uri,
            );

            $resposta = $this->actingAs($usuario)->get($url);
            $conferidas++;

            if ($resposta->getStatusCode() !== 403) {
                $vazadas[] = "{$url} devolveu {$resposta->getStatusCode()} para o perfil {$usuario->role} (esperado 403)";
            }
        }

        $this->assertGreaterThan(0, $conferidas, 'Nenhuma rota foi conferida — o filtro de middleware está errado.');
        $this->assertSame([], $vazadas, implode("\n", $vazadas));
    }

    /**
     * Rotas do projeto, ignorando as internas do framework.
     *
     * @return array<int, array{0: string, 1: string, 2: Route}>
     */
    private function rotasDaAplicacao(): array
    {
        $rotas = [];

        foreach (RouteFacade::getRoutes() as $rota) {
            /** @var Route $rota */
            $acao = $rota->getActionName();

            // Fora as rotas do Ignition, Telescope e afins
            if (str_contains($acao, 'Laravel\\') || str_contains($acao, 'Spatie\\')) {
                continue;
            }

            foreach ($rota->methods() as $metodo) {
                if (in_array($metodo, ['HEAD', 'OPTIONS'], true)) {
                    continue;
                }

                $rotas[] = [$metodo, '/'.ltrim($rota->uri(), '/'), $rota];
            }
        }

        return $rotas;
    }
}
