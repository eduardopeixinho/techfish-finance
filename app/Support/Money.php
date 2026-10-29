<?php

namespace App\Support;

use Illuminate\Support\Facades\Session;

/**
 * Value Object para formatação monetária que respeita a preferência
 * do usuário de ocultar valores.
 *
 * Por que um value object e não uma função helper?
 *  - View Composers injetam instâncias (compartilhamento fácil).
 *  - Estado (hideValues) encapsulado, em vez de leitura repetida
 *    da session em cada chamada de formatação.
 *  - Testável: o caller pode injetar uma instância mock com
 *    `new Money(hidden: true)` em testes de view.
 *
 * Comportamento:
 *  - Quando hideValues === false: delega para App\Support\Format
 *    (mesmo comportamento do helper original — sem regressão).
 *  - Quando hideValues === true: retorna a string de máscara.
 *
 * A leitura da preferência é feita **no construtor** (snapshot),
 * não a cada format() — assim se o user alterna a preferência e
 * recarrega a página, o composer cria nova instância e a view
 * renderiza com o estado atual.
 */
class Money
{
    /**
     * String exibida quando os valores estão ocultos.
     * Mantida curta e tipográfica — ocupa o mesmo espaço visual
     * que "R$ 1.234,56" para evitar layout shift.
     */
    public const MASK = 'R$ •••••';

    public function __construct(
        public bool $hidden = false,
    ) {}

    /**
     * Cria uma instância a partir da sessão atual.
     * Lê `session('hide_values')` (boolean).
     */
    public static function fromSession(): self
    {
        return new self(
            hidden: (bool) Session::get('hide_values', false)
        );
    }

    /**
     * Formata um valor monetário respeitando o estado de visibilidade.
     */
    public function format(float $valor): string
    {
        return $this->hidden ? self::MASK : Format::brl($valor);
    }

    /**
     * Formata BRL "compacto" (sem casas decimais) respeitando a visibilidade.
     * Use em kickers numéricos, chips — onde os centavos poluem.
     */
    public function formatCompact(float $valor): string
    {
        return $this->hidden ? self::MASK : Format::brlCompact($valor);
    }

    /**
     * Versão signed (com +/−) respeitando a visibilidade.
     */
    public function formatSigned(float $valor): string
    {
        return $this->hidden ? self::MASK : Format::brlSigned($valor);
    }

    /**
     * Atalho estático para views/partials que não recebem $money
     * via View Composer (ex: e-mails, jobs, views de log).
     * Lê a sessão na hora — use com moderação.
     */
    public static function display(float $valor): string
    {
        return self::fromSession()->format($valor);
    }
}
