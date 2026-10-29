<?php

namespace App\View\Composers;

use App\Support\PageTitle;
use Illuminate\View\View;

/**
 * View Composer: injeta `$title` em todas as views que renderizam
 * dentro de um layout contextual.
 *
 * Lê o nome da rota atual e mapeia via App\Support\PageTitle. O layout
 * consome via `$title ?? 'Fallback'`.
 *
 * Registrar em App\Providers\AppServiceProvider::boot():
 *   View::share('title', new TitleResolver()); // ver nota abaixo
 *
 * NOTA: usamos View::share (variável global) em vez de View::composer
 * (por view) porque o `$title` é lido pelo LAYOUT (layouts.*) que é
 * renderizado como componente aninhado do template da página.
 * View::composer em 'layouts.*' injeta em layouts.* mas a herança
 * para o slot do componente não é confiável. View::share garante
 * que toda view (incluindo layouts e slots) tem acesso a $title.
 */
class TitleComposer
{
    /**
     * Resolve o título da rota atual. Usado em View::share — o
     * callback é avaliado a cada render (porque a rota pode mudar).
     */
    public static function resolve(): ?string
    {
        $route = app('router')->current();

        return $route ? PageTitle::forRoute($route->getName()) : null;
    }
}
