<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGestor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || $request->user()->role !== 'gestor') {
            abort(403, 'Acesso restrito ao perfil gestor.');
        }

        return $next($request);
    }
}
