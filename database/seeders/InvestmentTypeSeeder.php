<?php

namespace Database\Seeders;

use App\Models\Investment\InvestmentType;
use Illuminate\Database\Seeder;

class InvestmentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'FII',
                'code' => 'fii',
                'description' => 'Fundos de Investimento Imobiliário',
                'has_dividends' => true,
                'color' => '#10B981',
            ],
            [
                'name' => 'Ação',
                'code' => 'stock',
                'description' => 'Ações Brasileiras',
                'has_dividends' => true,
                'color' => '#3B82F6',
            ],
            [
                'name' => 'BDR',
                'code' => 'bdr',
                'description' => 'Brazilian Depositary Receipts',
                'has_dividends' => true,
                'color' => '#8B5CF6',
            ],
            [
                'name' => 'ETF',
                'code' => 'etf',
                'description' => 'Exchange Traded Fund',
                'has_dividends' => false,
                'color' => '#F59E0B',
            ],
            [
                'name' => 'Stock',
                'code' => 'international_stock',
                'description' => 'Ações Internacionais',
                'has_dividends' => true,
                'color' => '#EF4444',
            ],
            [
                'name' => 'REIT',
                'code' => 'reit',
                'description' => 'Real Estate Investment Trust',
                'has_dividends' => true,
                'color' => '#84CC16',
            ],
        ];

        foreach ($types as $type) {
            InvestmentType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
