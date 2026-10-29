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
        ];
    }
}
