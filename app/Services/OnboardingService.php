<?php

namespace App\Services;

use App\Models\Finance\Account;
use App\Models\Finance\FinancialCategory;
use App\Models\Finance\Transaction;
use App\Models\User;

/**
 * "Primeiros passos" de quem acabou de entrar: conta → categorias → primeira transação.
 * O cartão do painel some quando os três estão feitos.
 */
class OnboardingService
{
    /**
     * Categorias sugeridas (nome => [tipo, cor]); criadas num clique no primeiro acesso.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const DEFAULT_CATEGORIES = [
        'Salário' => ['income', '#2F855A'],
        'Freelance' => ['income', '#38A169'],
        'Moradia' => ['expense', '#2B6CB0'],
        'Mercado' => ['expense', '#DD6B20'],
        'Transporte' => ['expense', '#D69E2E'],
        'Saúde' => ['expense', '#C53030'],
        'Lazer' => ['expense', '#805AD5'],
        'Assinaturas' => ['expense', '#4A5568'],
    ];

    /**
     * Passos e se cada um já foi feito; null quando não há mais nada a mostrar.
     *
     * @return array<string, bool>|null
     */
    public function steps(User $user): ?array
    {
        $steps = [
            'account' => Account::where('user_id', $user->id)->exists(),
            'categories' => FinancialCategory::where('user_id', $user->id)->exists(),
            'transaction' => Transaction::where('user_id', $user->id)->exists(),
        ];

        return in_array(false, $steps, true) ? $steps : null;
    }

    /**
     * Cria as categorias sugeridas que o usuário ainda não tem (pelo nome e tipo).
     *
     * @return int quantas foram criadas
     */
    public function createDefaultCategories(User $user): int
    {
        $created = 0;

        foreach (self::DEFAULT_CATEGORIES as $name => [$type, $color]) {
            $category = FinancialCategory::firstOrCreate(
                ['user_id' => $user->id, 'name' => $name, 'type' => $type],
                ['color' => $color, 'is_active' => true],
            );
            $created += $category->wasRecentlyCreated ? 1 : 0;
        }

        return $created;
    }
}
