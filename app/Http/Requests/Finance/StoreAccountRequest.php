<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'bank' => 'nullable|string|max:255',
            'type' => 'required|in:checking,savings,credit_card,cash,investment,other',
            'initial_balance' => 'required|numeric',
            'currency' => 'required|string|max:3',
            'color' => 'required|string|size:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'description' => 'nullable|string',
            // Cartão de crédito: dia de fechamento e de vencimento da fatura (1–31)
            'closing_day' => ['nullable', 'required_if:type,credit_card', 'integer', 'between:1,31'],
            'due_day' => ['nullable', 'required_if:type,credit_card', 'integer', 'between:1,31'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da conta é obrigatório.',
            'type.required' => 'O tipo de conta é obrigatório.',
            'type.in' => 'O tipo de conta deve ser: checking, savings, credit_card, cash, investment ou other.',
            'initial_balance.required' => 'O saldo inicial é obrigatório.',
            'color.required' => 'A cor é obrigatória.',
            'color.regex' => 'A cor deve ser um hexadecimal válido (ex: #FF5733).',
            'closing_day.required_if' => 'Informe o dia em que a fatura fecha.',
            'due_day.required_if' => 'Informe o dia em que a fatura vence.',
            'closing_day.between' => 'Use um dia entre 1 e 31.',
            'due_day.between' => 'Use um dia entre 1 e 31.',
        ];
    }
}
