<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCarregador
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || $request->user()->role !== 'carregador') {
            abort(403, 'Acesso restrito ao perfil carregador.');
        }

        return $next($request);
    }
}
