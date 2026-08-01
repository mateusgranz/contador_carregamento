<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Visitante na raiz é mandado para o login.
     */
    public function test_a_raiz_leva_visitante_para_o_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    /**
     * Usuário logado na raiz cai na área do seu perfil.
     */
    public function test_a_raiz_leva_usuario_logado_para_a_sua_area(): void
    {
        $carregador = User::factory()->create(['role' => 'carregador']);
        $this->actingAs($carregador)->get('/')->assertRedirect(route('dashboard'));

        $gestor = User::factory()->gestor()->create();
        $this->actingAs($gestor)->get('/')->assertRedirect(route('dashboard'));
    }
}
