<?php

namespace App\Http\Requests\Finance;

use App\Models\Finance\Account;
use App\Services\CreditCardBillService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * "Pagar fatura": de qual conta saiu o dinheiro, quanto (R$) e quando.
 * Não deixa pagar mais do que está em aberto.
 */
class PayBillRequest extends FormRequest
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
        return [
            'from_account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where('user_id', $this->user()->id)
                    ->whereNot('type', 'credit_card')
                    ->whereNull('deleted_at'),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['required', 'date'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $summary = app(CreditCardBillService::class)->getBillSummary($this->route('account'), $this->route('billIdentifier'));
                if ((float) $this->input('amount') > $summary['remaining_amount'] + 0.009) {
                    $validator->errors()->add('amount', 'Valor maior do que o que está em aberto nesta fatura.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'from_account_id.required' => 'Escolha de qual conta saiu o pagamento.',
            'from_account_id.exists' => 'Escolha uma das suas contas (que não seja cartão).',
            'amount.required' => 'Informe o valor pago.',
            'amount.min' => 'O valor deve ser maior que zero.',
            'paid_at.required' => 'Informe a data do pagamento.',
        ];
    }
}
