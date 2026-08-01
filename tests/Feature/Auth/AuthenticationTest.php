<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'code' => $user->code,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        // O login leva cada perfil direto para a sua área
        $response->assertRedirect(route('carregamento.index', absolute: false));
    }

    public function test_gestor_is_redirected_to_products_after_login(): void
    {
        $gestor = User::factory()->gestor()->create();

        $response = $this->post('/login', [
            'code' => $gestor->code,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('produtos.index', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'code' => $user->code,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_o_login_aceita_o_codigo_com_maiusculas_e_espacos(): void
    {
        User::factory()->create(['code' => 'joao']);

        $this->post('/login', [
            'code'     => '  JOAO ',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
    }

    public function test_manter_conectado_gera_o_cookie_de_lembranca(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'code'     => $user->code,
            'password' => 'password',
            'remember' => '1',
        ]);

        $this->assertAuthenticated();
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_nao_existe_autocadastro_nem_recuperacao_publica(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
        $this->get('/forgot-password')->assertNotFound();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
