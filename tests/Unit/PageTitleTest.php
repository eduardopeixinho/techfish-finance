<?php

namespace Tests\Unit;

use App\Support\PageTitle;
use PHPUnit\Framework\TestCase;

/**
 * Mapa rota → título (usado no <title> e na migalha de navegação).
 */
class PageTitleTest extends TestCase
{
    public function test_returns_title_for_known_route(): void
    {
        $this->assertSame('Painel', PageTitle::forRoute('finance.dashboard'));
        $this->assertSame('Faturas', PageTitle::forRoute('finance.bills.index'));
        $this->assertSame('Investimentos · Proventos', PageTitle::forRoute('investment.reports.dividends'));
    }

    public function test_returns_null_for_unknown_route(): void
    {
        $this->assertNull(PageTitle::forRoute('rota.inexistente'));
        $this->assertNull(PageTitle::forRoute(''));
        $this->assertNull(PageTitle::forRoute(null));
    }

    public function test_fallback_when_route_unknown(): void
    {
        $this->assertSame('Padrão', PageTitle::forRouteOr('rota.inexistente', 'Padrão'));
        $this->assertSame('Painel', PageTitle::forRouteOr('finance.dashboard', 'IGNORADO'));
    }

    public function test_admin_routes_have_admin_prefix(): void
    {
        $this->assertStringStartsWith('Admin', PageTitle::forRoute('admin.roles.index'));
        $this->assertStringStartsWith('Admin', PageTitle::forRoute('admin.permissions.index'));
        $this->assertStringStartsWith('Admin', PageTitle::forRoute('admin.user-roles.index'));
    }
}
