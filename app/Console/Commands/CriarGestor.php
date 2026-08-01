<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Cria ou atualiza um usuário gestor.
 *
 * Como não existe autocadastro público, este comando é a única forma de
 * criar o primeiro acesso em um banco novo — e de recuperar o acesso
 * caso a senha do gestor se perca.
 */
class CriarGestor extends Command
{
    protected $signature = 'usuario:gestor
                            {--code= : Código de acesso (ex.: admin)}
                            {--name= : Nome da pessoa}
                            {--password= : Senha, mínimo 6 caracteres}';

    protected $description = 'Cria (ou redefine) um usuário com perfil gestor';

    public function handle(): int
    {
        $code = Str::lower(trim((string) ($this->option('code') ?: $this->ask('Código de acesso', 'admin'))));
        $name = trim((string) ($this->option('name') ?: $this->ask('Nome', 'Administrador')));

        $password = (string) ($this->option('password') ?: $this->secret('Senha (mínimo 6 caracteres)'));

        if (! preg_match('/^[a-z0-9._-]+$/', $code)) {
            $this->error('O código aceita apenas letras minúsculas, números, ponto, hífen e underline.');

            return self::FAILURE;
        }

        if (strlen($password) < 6) {
            $this->error('A senha precisa ter ao menos 6 caracteres.');

            return self::FAILURE;
        }

        $existente = User::where('code', $code)->first();

        $usuario = User::updateOrCreate(
            ['code' => $code],
            [
                'name'     => $name,
                'password' => Hash::make($password),
                'role'     => 'gestor',
            ],
        );

        $this->info($existente
            ? "Usuário \"{$code}\" atualizado: agora é gestor e a senha foi redefinida."
            : "Gestor \"{$code}\" criado.");

        $this->line("Entre em /login com o código: {$usuario->code}");

        return self::SUCCESS;
    }
}
