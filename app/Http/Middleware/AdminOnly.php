<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOnly
{
    /**
     * Permite acesso apenas a usuários com papel de administrador.
     */
    public function handle(Request $request, Closure $next)
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            abort(403, 'Acesso restrito a administradores.');
        }

        return $next($request);
    }
}
