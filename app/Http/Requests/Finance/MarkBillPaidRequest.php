<?php

namespace App\Http\Requests\Finance;

use App\Models\Finance\Account;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * "Marcar como paga": sem campos; só o dono do cartão.
 */
class MarkBillPaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        $card = $this->route('account');

        return $card instanceof Account && $card->isCreditCard() && $this->user()->can('update', $card);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
