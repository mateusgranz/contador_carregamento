<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $usuarios = User::orderBy('name')->get();

        return view('usuarios.index', compact('usuarios'));
    }

    public function create(): View
    {
        return view('usuarios.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create([
            'code'     => $request->code,
            'name'     => $request->name,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        return redirect()->route('usuarios.index')
            ->with('sucesso', "Usuário \"{$request->name}\" criado. Ele entra com o código {$request->code}.");
    }

    public function edit(User $usuario): View
    {
        return view('usuarios.edit', compact('usuario'));
    }

    public function update(UpdateUserRequest $request, User $usuario): RedirectResponse
    {
        // Impede o gestor de tirar o próprio acesso e ficar sem quem administre
        if ($usuario->is($request->user()) && $request->role !== 'gestor') {
            return back()->withInput()
                ->withErrors(['role' => 'Você não pode mudar o seu próprio perfil para carregador.']);
        }

        $dados = [
            'code' => $request->code,
            'name' => $request->name,
            'role' => $request->role,
        ];

        // Senha em branco significa "manter a atual"
        if (filled($request->password)) {
            $dados['password'] = Hash::make($request->password);
        }

        $usuario->update($dados);

        return redirect()->route('usuarios.index')
            ->with('sucesso', 'Usuário atualizado.');
    }

    public function destroy(User $usuario): RedirectResponse
    {
        if ($usuario->is(auth()->user())) {
            return back()->withErrors(['usuario' => 'Você não pode excluir o seu próprio usuário.']);
        }

        // Carregamentos apagam junto por cascade — avisa antes de perder histórico
        $usuario->delete();

        return redirect()->route('usuarios.index')
            ->with('sucesso', 'Usuário excluído.');
    }
}
