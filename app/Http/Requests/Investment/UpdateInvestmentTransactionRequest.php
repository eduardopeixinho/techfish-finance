<?php

namespace App\Http\Requests\Investment;

use App\Models\Investment\InvestmentTransaction;

/**
 * Mesmas regras do cadastro; a checagem de posição troca o lançamento antigo pela versão editada.
 */
class UpdateInvestmentTransactionRequest extends StoreInvestmentTransactionRequest
{
    protected function editedTransaction(): ?InvestmentTransaction
    {
        return $this->route('transaction');
    }
}
