<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * Preferências de UI do usuário autenticado.
 *
 * Persistência: session (não cookie, não coluna no banco).
 * Justificativa: preferências de visualização devem ser efêmeras
 * (não poluem o perfil nem disparam GDPR). O user está autenticado
 * em todas as páginas onde o toggle importa.
 *
 * Toggle: o método `toggleValues()` inverte o estado e volta para
 * a página anterior com `back()`. Sem JS, sem redirect para uma
 * rota dedicada — UX direta.
 */
class PreferenceController extends Controller
{
    /**
     * Inverte `hide_values` na sessão e volta para a página anterior.
     */
    public function toggleValues(Request $request)
    {
        $current = (bool) Session::get('hide_values', false);
        Session::put('hide_values', ! $current);

        return back();
    }
}
