<?php

namespace Database\Seeders;

use App\Models\Finance\Settings\AccountType;
use App\Models\Finance\Settings\IncomeType;
use App\Models\Finance\Settings\PaymentMethod;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tipos de Conta
        $accountTypes = [
            ['name' => 'Conta Corrente', 'description' => 'Conta bancária para movimentações do dia a dia'],
            ['name' => 'Conta Poupança', 'description' => 'Conta para guardar dinheiro com rendimento'],
            ['name' => 'Conta Investimento', 'description' => 'Conta para aplicações e investimentos'],
            ['name' => 'Carteira Digital', 'description' => 'Aplicativos de pagamento (PicPay, PayPal, etc.)'],
            ['name' => 'Dinheiro', 'description' => 'Dinheiro físico em espécie'],
            ['name' => 'Cartão de Crédito', 'description' => 'Limite do cartão de crédito'],
        ];

        foreach ($accountTypes as $type) {
            AccountType::create($type);
        }

        // Tipos de Receita
        $incomeTypes = [
            ['name' => 'Salário', 'description' => 'Salário mensal do trabalho'],
            ['name' => 'Freelance', 'description' => 'Trabalhos freelancer e projetos'],
            ['name' => 'Vendas', 'description' => 'Vendas de produtos ou serviços'],
            ['name' => 'Investimentos', 'description' => 'Rendimentos de investimentos'],
            ['name' => 'Presente', 'description' => 'Dinheiro recebido como presente'],
            ['name' => 'Reembolso', 'description' => 'Dinheiro devolvido ou reembolsado'],
            ['name' => 'Aluguel', 'description' => 'Recebimento de aluguel de imóveis'],
            ['name' => 'Aposentadoria', 'description' => 'Benefício de aposentadoria'],
            ['name' => 'Auxílio', 'description' => 'Auxílios governamentais ou empresariais'],
            ['name' => 'Outros', 'description' => 'Outras fontes de receita'],
        ];

        foreach ($incomeTypes as $type) {
            IncomeType::create($type);
        }

        // Métodos de Pagamento
        $paymentMethods = [
            ['name' => 'Dinheiro', 'description' => 'Pagamento em dinheiro físico', 'active' => true],
            ['name' => 'Cartão de Débito', 'description' => 'Pagamento com cartão de débito', 'active' => true],
            ['name' => 'Cartão de Crédito', 'description' => 'Pagamento com cartão de crédito', 'active' => true],
            ['name' => 'PIX', 'description' => 'Transferência instantânea via PIX', 'active' => true],
            ['name' => 'TED/DOC', 'description' => 'Transferência bancária tradicional', 'active' => true],
            ['name' => 'Boleto', 'description' => 'Pagamento via boleto bancário', 'active' => true],
            ['name' => 'PayPal', 'description' => 'Pagamento via PayPal', 'active' => true],
            ['name' => 'PicPay', 'description' => 'Pagamento via PicPay', 'active' => true],
            ['name' => 'Mercado Pago', 'description' => 'Pagamento via Mercado Pago', 'active' => true],
            ['name' => 'Apple Pay', 'description' => 'Pagamento via Apple Pay', 'active' => false],
            ['name' => 'Google Pay', 'description' => 'Pagamento via Google Pay', 'active' => false],
            ['name' => 'Cheque', 'description' => 'Pagamento via cheque', 'active' => false],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::create($method);
        }
    }
}
