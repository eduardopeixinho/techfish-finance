<?php

namespace App\Http\Requests\Investment;

use Illuminate\Validation\Rules\Unique;

/**
 * Mesmas regras do cadastro; o ticker único ignora o próprio ativo.
 */
class UpdateInvestmentRequest extends StoreInvestmentRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('investment'));
    }

    protected function uniqueTickerRule(): Unique
    {
        return parent::uniqueTickerRule()->ignore($this->route('investment'));
    }
}
