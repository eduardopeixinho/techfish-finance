<?php

namespace App\Http\Requests\Investment;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvestmentDividendRequest extends FormRequest
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
            'investment_id' => 'required|exists:investments,id',
            'type' => 'required|in:dividend,jscp,rent,bonus,split,grouping',
            'amount_per_share' => 'required|numeric|min:0',
            'quantity_owned' => 'required|integer|min:1',
            'record_date' => 'required|date',
            'payment_date' => 'required|date|after_or_equal:record_date',
            'tax_withheld' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
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
            'investment_id.required' => 'O investimento é obrigatório.',
            'investment_id.exists' => 'O investimento selecionado não existe.',
            'type.required' => 'O tipo é obrigatório.',
            'type.in' => 'O tipo deve ser: dividendo, JCP, aluguel, bonificação, desdobramento ou grupamento.',
            'amount_per_share.required' => 'O valor por cota é obrigatório.',
            'amount_per_share.numeric' => 'O valor por cota deve ser um número.',
            'amount_per_share.min' => 'O valor por cota não pode ser negativo.',
            'quantity_owned.required' => 'A quantidade possuída é obrigatória.',
            'quantity_owned.integer' => 'A quantidade deve ser um número inteiro.',
            'quantity_owned.min' => 'A quantidade deve ser maior que zero.',
            'record_date.required' => 'A data de registro é obrigatória.',
            'record_date.date' => 'A data de registro deve ser uma data válida.',
            'payment_date.required' => 'A data de pagamento é obrigatória.',
            'payment_date.date' => 'A data de pagamento deve ser uma data válida.',
            'payment_date.after_or_equal' => 'A data de pagamento deve ser igual ou posterior à data de registro.',
            'tax_withheld.numeric' => 'O imposto retido deve ser um número.',
            'tax_withheld.min' => 'O imposto retido não pode ser negativo.',
            'notes.max' => 'As observações não podem ter mais que 1000 caracteres.',
        ];
    }
}
