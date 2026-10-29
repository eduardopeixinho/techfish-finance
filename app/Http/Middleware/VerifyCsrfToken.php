<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Http\Request;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];

    /**
     * Determine if the request has a URI that should pass through CSRF verification.
     * Em ambiente de teste, desabilita CSRF para todos os requests.
     *
     * @param  Request  $request
     * @return bool
     */
    protected function inExceptArray($request)
    {
        // Desabilita CSRF em ambiente de teste
        if (app()->environment('testing') || app()->runningInConsole()) {
            return true;
        }

        return parent::inExceptArray($request);
    }
}
