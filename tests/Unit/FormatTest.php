<?php

namespace Tests\Unit;

use App\Support\Format;
use PHPUnit\Framework\TestCase;

/**
 * Cobre App\Support\Format (helper de formatação para UI).
 *
 * Regras:
 *  - PT-BR (BRL, vírgula decimal)
 *  - Sempre explícito (R$ prefixado)
 *  - Sem dependência de request/session
 */
class FormatTest extends TestCase
{
    public function test_brl_formata_com_duas_casas_e_separador_br(): void
    {
        $this->assertSame('R$ 1.234,56', Format::brl(1234.56));
        $this->assertSame('R$ 0,00', Format::brl(0.0));
        $this->assertSame('R$ −1.000,00', Format::brl(-1000.0));
        // Arredondou para zero: sem sinal
        $this->assertSame('R$ 0,00', Format::brl(-0.004));
    }

    public function test_brl_arredonda_para_duas_casas(): void
    {
        // 1.005 vira 1,01 com number_format (banker rounding pode variar,
        // mas queremos consistência visual — o usuário não vai notar 0,001)
        $this->assertSame('R$ 1,01', Format::brl(1.005));
    }

    public function test_brl_compact_omite_centavos(): void
    {
        // 1234.4 arredonda para 1234 (não estoura)
        $this->assertSame('R$ 1.234', Format::brlCompact(1234.4));
        $this->assertSame('R$ 0', Format::brlCompact(0.0));
        $this->assertSame('R$ −1.000', Format::brlCompact(-1000.4));
        $this->assertSame('R$ 0', Format::brlCompact(-0.4));
    }

    public function test_brl_compact_arredonda_para_inteiro(): void
    {
        $this->assertSame('R$ 1', Format::brlCompact(1.4));
        $this->assertSame('R$ 2', Format::brlCompact(1.6));
    }

    public function test_brl_signed_prefixa_sinal_explicito(): void
    {
        $this->assertSame('R$ +1.234,56', Format::brlSigned(1234.56));
        // Sinal negativo usa U+2212 (minus sign tipográfico), não hífen
        $this->assertSame('R$ −1.234,56', Format::brlSigned(-1234.56));
        $this->assertSame('R$ 0,00', Format::brlSigned(0.0));
        // Saldo e variação negativos usam o mesmo sinal
        $this->assertSame(Format::brl(-11459.40), Format::brlSigned(-11459.40));
    }

    public function test_percent_format_com_virgula_decimal(): void
    {
        $this->assertSame('12,5%', Format::percent(12.5));
        $this->assertSame('100,0%', Format::percent(100.0));
        $this->assertSame('0,0%', Format::percent(0.0));
        $this->assertSame('33,33%', Format::percent(33.333, 2));
    }

    public function test_days_between_retorna_diferenca_com_sinal(): void
    {
        $hoje = new \DateTimeImmutable('2026-06-06');
        $amanha = new \DateTimeImmutable('2026-06-07');
        $ontem = new \DateTimeImmutable('2026-06-05');

        $this->assertSame(1, Format::daysBetween($hoje, $amanha));
        $this->assertSame(-1, Format::daysBetween($hoje, $ontem));
    }

    public function test_days_between_aceita_strings(): void
    {
        $this->assertSame(
            5,
            Format::daysBetween('2026-06-01', '2026-06-06')
        );
    }
}
