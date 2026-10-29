<?php

namespace App\Http\Requests\Finance;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:income,expense,transfer',
            'transaction_date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'category_id' => 'nullable|exists:financial_categories,id',
            'transfer_account_id' => ['nullable', 'required_if:type,transfer', 'exists:accounts,id', 'different:account_id'],
            'notes' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'tags' => 'nullable|array',
            // Fatura escolhida no formulário ("MM-AAAA", mês do vencimento); vazio = calculada pela data
            'bill_identifier' => ['nullable', 'regex:/^(0[1-9]|1[0-2])-\d{4}$/'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'description.required' => 'A descrição é obrigatória.',
            'amount.required' => 'O valor é obrigatório.',
            'amount.min' => 'O valor deve ser maior que zero.',
            'type.required' => 'O tipo é obrigatório.',
            'type.in' => 'O tipo deve ser receita, despesa ou transferência.',
            'transaction_date.required' => 'A data é obrigatória.',
            'transaction_date.date' => 'A data deve ser uma data válida.',
            'account_id.required' => 'A conta é obrigatória.',
            'account_id.exists' => 'A conta selecionada não existe.',
            'bill_identifier.regex' => 'Fatura inválida.',
        ];
    }
}
