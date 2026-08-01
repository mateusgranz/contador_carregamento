<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Ferramenta interna: o acesso passa a ser por código de usuário,
     * não por e-mail. O e-mail vira opcional e deixa de ser exigido.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('id');
        });

        // Usuários existentes ganham um código a partir do início do e-mail
        foreach (DB::table('users')->whereNull('code')->get() as $usuario) {
            DB::table('users')->where('id', $usuario->id)->update([
                'code' => Str::slug(explode('@', (string) $usuario->email)[0]) ?: 'user'.$usuario->id,
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
