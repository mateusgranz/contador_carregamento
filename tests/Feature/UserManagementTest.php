<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $gestor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gestor = User::factory()->gestor()->create(['code' => 'chefe']);
    }

    public function test_carregador_nao_acessa_a_gestao_de_usuarios(): void
    {
        $carregador = User::factory()->create(['role' => 'carregador']);

        $this->actingAs($carregador)->get('/usuarios')->assertForbidden();
        $this->actingAs($carregador)->get('/usuarios/criar')->assertForbidden();
    }

    public function test_gestor_cadastra_usuario_com_codigo_nome_e_senha(): void
    {
        $this->actingAs($this->gestor)->post('/usuarios', [
            'code'     => 'joao',
            'name'     => 'João da Silva',
            'password' => 'patio123',
            'role'     => 'carregador',
        ])->assertRedirect(route('usuarios.index'));

        $novo = User::where('code', 'joao')->first();

        $this->assertSame('João da Silva', $novo->name);
        $this->assertSame('carregador', $novo->role);
        $this->assertTrue(Hash::check('patio123', $novo->password));
        // Ferramenta interna: e-mail não é pedido
        $this->assertNull($novo->email);
    }

    public function test_usuario_cadastrado_consegue_entrar_com_o_codigo(): void
    {
        $this->actingAs($this->gestor)->post('/usuarios', [
            'code'     => 'maria',
            'name'     => 'Maria',
            'password' => 'patio123',
            'role'     => 'carregador',
        ]);

        $this->post('/logout');

        $this->post('/login', ['code' => 'maria', 'password' => 'patio123'])
            ->assertRedirect(route('carregamento.index'));

        $this->assertAuthenticated();
    }

    public function test_codigo_e_normalizado_para_minusculas(): void
    {
        $this->actingAs($this->gestor)->post('/usuarios', [
            'code'     => '  JOAO.SILVA ',
            'name'     => 'João',
            'password' => 'patio123',
            'role'     => 'carregador',
        ]);

        $this->assertNotNull(User::where('code', 'joao.silva')->first());
    }

    public function test_codigo_duplicado_e_recusado(): void
    {
        User::factory()->create(['code' => 'joao']);

        $this->actingAs($this->gestor)->post('/usuarios', [
            'code'     => 'joao',
            'name'     => 'Outro João',
            'password' => 'patio123',
            'role'     => 'carregador',
        ])->assertSessionHasErrors('code');
    }

    public function test_codigo_com_espaco_ou_acento_e_recusado(): void
    {
        $this->actingAs($this->gestor)->post('/usuarios', [
            'code'     => 'joão silva',
            'name'     => 'João',
            'password' => 'patio123',
            'role'     => 'carregador',
        ])->assertSessionHasErrors('code');
    }

    public function test_senha_curta_e_recusada(): void
    {
        $this->actingAs($this->gestor)->post('/usuarios', [
            'code'     => 'joao',
            'name'     => 'João',
            'password' => '123',
            'role'     => 'carregador',
        ])->assertSessionHasErrors('password');
    }

    public function test_editar_sem_senha_mantem_a_senha_atual(): void
    {
        $usuario = User::factory()->create([
            'code'     => 'joao',
            'password' => Hash::make('senha-antiga'),
            'role'     => 'carregador',
        ]);

        $this->actingAs($this->gestor)->patch(route('usuarios.update', $usuario), [
            'code'     => 'joao',
            'name'     => 'João Editado',
            'password' => '',
            'role'     => 'carregador',
        ])->assertRedirect(route('usuarios.index'));

        $usuario->refresh();
        $this->assertSame('João Editado', $usuario->name);
        $this->assertTrue(Hash::check('senha-antiga', $usuario->password));
    }

    public function test_gestor_troca_a_senha_de_um_usuario(): void
    {
        $usuario = User::factory()->create(['code' => 'joao', 'role' => 'carregador']);

        $this->actingAs($this->gestor)->patch(route('usuarios.update', $usuario), [
            'code'     => 'joao',
            'name'     => $usuario->name,
            'password' => 'nova-senha',
            'role'     => 'carregador',
        ]);

        $this->assertTrue(Hash::check('nova-senha', $usuario->fresh()->password));
    }

    public function test_gestor_nao_rebaixa_o_proprio_perfil(): void
    {
        $this->actingAs($this->gestor)->patch(route('usuarios.update', $this->gestor), [
            'code'     => 'chefe',
            'name'     => $this->gestor->name,
            'password' => '',
            'role'     => 'carregador',
        ])->assertSessionHasErrors('role');

        $this->assertSame('gestor', $this->gestor->fresh()->role);
    }

    public function test_gestor_nao_exclui_o_proprio_usuario(): void
    {
        $this->actingAs($this->gestor)
            ->delete(route('usuarios.destroy', $this->gestor))
            ->assertSessionHasErrors('usuario');

        $this->assertNotNull($this->gestor->fresh());
    }

    public function test_gestor_exclui_outro_usuario(): void
    {
        $usuario = User::factory()->create(['code' => 'joao', 'role' => 'carregador']);

        $this->actingAs($this->gestor)
            ->delete(route('usuarios.destroy', $usuario))
            ->assertRedirect(route('usuarios.index'));

        $this->assertNull(User::find($usuario->id));
    }

    public function test_listagem_mostra_codigo_nome_e_perfil(): void
    {
        User::factory()->create(['code' => 'joao', 'name' => 'João da Silva', 'role' => 'carregador']);

        $this->actingAs($this->gestor)->get('/usuarios')
            ->assertOk()
            ->assertSee('joao')
            ->assertSee('João da Silva')
            ->assertSee('Carregador');
    }
}
