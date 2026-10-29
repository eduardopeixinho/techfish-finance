<?php

namespace App\Support;

/**
 * Helpers de formatação de UI.
 *
 * Centraliza regras de apresentação (moeda, números) que são compartilhadas
 * entre views e services. O objetivo é evitar closures inline em Blade e
 * duplicação entre Finance / Investment / Dashboard.
 *
 * Convenções:
 *  - Sempre em PT-BR (BRL, vírgula decimal, ponto de milhar).
 *  - Explicitar unidades (R$) — nunca confiar em cast silencioso.
 *  - Métodos puros, sem dependência de request/session (testáveis direto).
 */
class Format
{
    /**
     * Formata um valor monetário em BRL completo: "R$ 1.234,56".
     * Use em saldos, totais, faturas — onde os centavos importam.
     */
    public static function brl(float $valor): string
    {
        return 'R$ '.number_format($valor, 2, ',', '.');
    }

    /**
     * Formata BRL "compacto" (sem casas decimais): "R$ 1.234".
     * Use em kickers numéricos, chips, dashboards — onde os centavos
     * poluem visualmente. Arredonda para o inteiro mais próximo
     * (banker's rounding não é necessário para UI).
     */
    public static function brlCompact(float $valor): string
    {
        return 'R$ '.number_format(round($valor), 0, ',', '.');
    }

    /**
     * Formata um valor com sinal explícito: "R$ +1.234,56" / "R$ -1.234,56".
     * Use em deltas, variações mensais, fluxo de caixa — onde a direção
     * do número é tão importante quanto o valor.
     */
    public static function brlSigned(float $valor): string
    {
        $sinal = $valor > 0 ? '+' : ($valor < 0 ? '−' : '');

        // number_format preserva o sinal; só precisamos prefixar com +/−
        return 'R$ '.$sinal.number_format(abs($valor), 2, ',', '.');
    }

    /**
     * Formata um percentual com 1 casa decimal: "12,5%".
     * Não multiplica — espera entrada já em 0..100 (não 0..1).
     */
    public static function percent(float $valor, int $decimals = 1): string
    {
        return number_format($valor, $decimals, ',', '.').'%';
    }

    /**
     * Diferença em dias entre duas datas (inclusiva do sinal: b - a).
     * Wrapper para deixar views e services mais legíveis.
     */
    public static function daysBetween(\DateTimeInterface|string $a, \DateTimeInterface|string $b): int
    {
        $a = $a instanceof \DateTimeInterface ? $a : new \DateTimeImmutable($a);
        $b = $b instanceof \DateTimeInterface ? $b : new \DateTimeImmutable($b);

        return (int) $a->diff($b)->days * ($a > $b ? -1 : 1);
    }
}
