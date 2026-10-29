<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Layout do contexto Admin.
 * Identidade: paper + tipografia mono para "infra" (não é produto, é engenharia).
 * Renderiza resources/views/layouts/admin.blade.php.
 */
class AdminLayout extends Component
{
    public function render(): View
    {
        return view('layouts.admin');
    }
}
