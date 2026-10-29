<?php

namespace App\Support;

/**
 * Resolve o `<title>` (e a migalha de navegação) da página a partir do nome da rota.
 *
 * Mantém os títulos centralizados em código (adicionar rota = adicionar linha).
 * Quando uma rota não está mapeada, retorna null e o layout cai no fallback dele.
 */
class PageTitle
{
    /**
     * Mapa rota → título legível.
     */
    protected static array $map = [
        // Finanças
        'finance.dashboard' => 'Painel',
        'finance.reports' => 'Relatórios',
        'finance.bills.index' => 'Faturas',
        'finance.transactions.index' => 'Transações',
        'finance.accounts.index' => 'Contas',
        'finance.categories.index' => 'Categorias',

        // Investimentos
        'investment.dashboard' => 'Investimentos',
        'investment.investments.index' => 'Investimentos · Carteira',
        'investment.reports' => 'Investimentos · Relatórios',
        'investment.reports.portfolio' => 'Investimentos · Carteira detalhada',
        'investment.reports.dividends' => 'Investimentos · Proventos',

        // Admin
        'admin.roles.index' => 'Admin · Perfis',
        'admin.permissions.index' => 'Admin · Permissões',
        'admin.user-roles.index' => 'Admin · Usuários',

        // Conta
        'profile.view' => 'Perfil',
    ];

    /**
     * Retorna o título para um nome de rota, ou null se não mapeado.
     */
    public static function forRoute(?string $routeName): ?string
    {
        if ($routeName === null) {
            return null;
        }

        return self::$map[$routeName] ?? null;
    }

    /**
     * Retorna o título resolvido ou um fallback explícito.
     */
    public static function forRouteOr(?string $routeName, string $fallback): string
    {
        return self::forRoute($routeName) ?? $fallback;
    }
}
