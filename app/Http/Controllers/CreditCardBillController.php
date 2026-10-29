<?php

namespace App\Http\Controllers;

use App\Models\Finance\Account;
use App\Services\CreditCardBillService;

class CreditCardBillController extends Controller
{
    protected $billService;

    public function __construct(CreditCardBillService $billService)
    {
        $this->billService = $billService;
    }

    /**
     * Lista todas as faturas de cartões de crédito
     */
    public function index()
    {
        $creditCardAccounts = Account::where('type', 'credit_card')
            ->where('user_id', auth()->id())
            ->get();

        $bills = [];
        foreach ($creditCardAccounts as $account) {
            $accountBills = $this->billService->getAvailableBills($account);
            foreach ($accountBills as $bill) {
                $bills[] = array_merge($bill, ['account' => $account]);
            }
        }

        // Mais recente primeiro (pelo vencimento)
        usort($bills, fn (array $a, array $b): int => $b['due_date'] <=> $a['due_date']);

        return view('finance.bills.index', compact('bills'));
    }

    /**
     * Mostra detalhes de uma fatura específica
     */
    public function show(Account $account, string $billIdentifier)
    {
        $this->authorize('view', $account);

        $summary = $this->billService->getBillSummary($account, $billIdentifier);
        $transactions = $this->billService->getTransactionsByBill($account, $billIdentifier);
        // Contas de onde o pagamento pode sair (não cartões)
        $fromAccounts = Account::forUser(auth()->id())->active()->where('type', '!=', 'credit_card')->orderBy('name')->get();

        return view('finance.bills.show', compact('account', 'summary', 'transactions', 'fromAccounts'));
    }

    /**
     * API para obter dados da fatura para gráficos
     */
    public function apiSummary(Account $account, string $billIdentifier)
    {
        $this->authorize('view', $account);

        $summary = $this->billService->getBillSummary($account, $billIdentifier);

        // Calcular percentuais para as categorias
        $totalAmount = $summary['total_amount'];
        if ($totalAmount > 0) {
            foreach ($summary['categories'] as &$category) {
                $category['percentage'] = round(($category['total_amount'] / $totalAmount) * 100, 1);
            }
        }

        return response()->json($summary);
    }
}
