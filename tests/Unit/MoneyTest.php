<?php

namespace Tests\Unit;

use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * Cobre App\Support\Money (value object que respeita a preferência
 * `hide_values` da sessão).
 *
 * Comportamento:
 *  - hidden=false → formata normalmente (delegando para App\Support\Format)
 *  - hidden=true  → retorna a constante MASK ("R$ •••••")
 */
class MoneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_format_normal_when_not_hidden(): void
    {
        Session::put('hide_values', false);
        $money = Money::fromSession();

        $this->assertSame('R$ 1.234,56', $money->format(1234.56));
        $this->assertSame('R$ 0,00', $money->format(0.0));
        $this->assertSame('R$ −1.000,00', $money->format(-1000.0));
    }

    public function test_format_returns_mask_when_hidden(): void
    {
        Session::put('hide_values', true);
        $money = Money::fromSession();

        $this->assertSame(Money::MASK, $money->format(1234.56));
        $this->assertSame(Money::MASK, $money->format(0.0));
        $this->assertSame(Money::MASK, $money->format(-1000.0));
    }

    public function test_format_compact_respects_hidden(): void
    {
        Session::put('hide_values', true);
        $money = Money::fromSession();

        $this->assertSame(Money::MASK, $money->formatCompact(1234.56));
    }

    public function test_format_compact_normal_when_not_hidden(): void
    {
        Session::put('hide_values', false);
        $money = Money::fromSession();

        // 1234.4 arredonda para 1234 (não estoura)
        $this->assertSame('R$ 1.234', $money->formatCompact(1234.4));
    }

    public function test_format_signed_respects_hidden(): void
    {
        Session::put('hide_values', true);
        $money = Money::fromSession();

        $this->assertSame(Money::MASK, $money->formatSigned(1234.56));
    }

    public function test_default_session_value_is_visible(): void
    {
        // Sem put na sessão: estado default = visível
        Session::forget('hide_values');
        $money = Money::fromSession();

        $this->assertFalse($money->hidden);
        $this->assertSame('R$ 100,00', $money->format(100.0));
    }

    public function test_explicit_constructor_argument_takes_precedence(): void
    {
        // Independente da sessão, o construtor aceita um valor explícito
        // (útil em testes de view com mock do Money)
        $money = new Money(hidden: true);
        $this->assertTrue($money->hidden);
        $this->assertSame(Money::MASK, $money->format(1234.56));
    }

    public function test_display_helper_reads_session_directly(): void
    {
        // Money::display() é atalho estático para casos excepcionais
        // (e-mails, jobs, logs). Lê a sessão na hora.
        Session::put('hide_values', true);
        $this->assertSame(Money::MASK, Money::display(500.0));

        Session::put('hide_values', false);
        $this->assertSame('R$ 500,00', Money::display(500.0));
    }
}
