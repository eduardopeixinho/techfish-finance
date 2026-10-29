<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Configuração de UI dos catálogos de Configurações (admin), consumida pelas
 * views genéricas em `resources/views/finance/settings/_crud/`.
 *
 * Os três catálogos têm o mesmo formato (nome + descrição [+ ativo]); cada
 * view de recurso só escolhe a chave e repassa o(s) modelo(s).
 */
class SettingsCatalog
{
    /**
     * @var array<string, array{title: string, singular: string, new: string, route: string, description: string, activeField: string|null}>
     */
    protected static array $catalogs = [
        'account-types' => [
            'title' => 'Tipos de conta',
            'singular' => 'tipo de conta',
            'new' => 'Novo tipo de conta',
            'route' => 'settings.account-types',
            'description' => 'Catálogo de referência de tipos de conta.',
            'activeField' => null,
        ],
        'income-types' => [
            'title' => 'Tipos de receita',
            'singular' => 'tipo de receita',
            'new' => 'Novo tipo de receita',
            'route' => 'settings.income-types',
            'description' => 'Catálogo de referência das origens de receita (salário, freelance, vendas...).',
            'activeField' => null,
        ],
        'payment-methods' => [
            'title' => 'Formas de pagamento',
            'singular' => 'forma de pagamento',
            'new' => 'Nova forma de pagamento',
            'route' => 'settings.payment-methods',
            'description' => 'As formas ativas aparecem no campo "Forma de pagamento" ao lançar transações.',
            'activeField' => 'active',
        ],
    ];

    /**
     * @return array{title: string, singular: string, new: string, route: string, description: string, activeField: string|null}
     */
    public static function get(string $key): array
    {
        return static::$catalogs[$key] ?? throw new InvalidArgumentException("Catálogo de configurações desconhecido: {$key}");
    }
}
