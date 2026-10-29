<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Layout do contexto Financeiro.
 * Identidade: paper (#F5F1E8), Fraunces nos títulos, âmbar como accent.
 * Renderiza resources/views/layouts/finance.blade.php.
 *
 * Usado por todas as views de finance.* e investment.* (mesmo contexto).
 */
class FinanceLayout extends Component
{
    public function render(): View
    {
        return view('layouts.finance');
    }
}
