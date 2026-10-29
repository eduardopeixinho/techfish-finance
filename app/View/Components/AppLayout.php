<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * Telas fora de Finanças/Investimentos (ex.: perfil) usam o mesmo layout do app.
     */
    public function render(): View
    {
        return view('layouts.finance');
    }
}
