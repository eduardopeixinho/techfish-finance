<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Derruba a sessão de um usuário desativado pelo admin (grupo `web`).
 *
 * O login pelo Google já recusa contas desativadas; este middleware cobre quem
 * estava logado (inclusive pelo "lembrar de mim") no momento da desativação.
 */
class EnsureUserIsActive
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::user()?->isDeactivated()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort_if($request->expectsJson(), 403, 'Conta desativada.');

            return redirect()->route('login')->with('error', 'Sua conta foi desativada. Fale com o administrador.');
        }

        return $next($request);
    }
}
