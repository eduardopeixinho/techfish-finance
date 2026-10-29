<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeçalhos de segurança em todas as respostas web.
 *
 * - X-Frame-Options / frame-ancestors: impede que o app seja embutido em
 *   outro site (clickjacking). Não afeta o player: lá nós embutimos o YouTube.
 * - HSTS: só em HTTPS, para não travar o ambiente local em http://localhost.
 */
class SecurityHeaders
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'");
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Não anunciar a versão do PHP (cabeçalho adicionado pelo próprio PHP, fora da Response).
        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }

        return $response;
    }
}
