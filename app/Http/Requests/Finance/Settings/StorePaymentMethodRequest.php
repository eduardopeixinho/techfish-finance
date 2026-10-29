<?php

namespace App\Http\Requests\Finance\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:payment_methods',
            'description' => 'nullable|string|max:500',
            'active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do método de pagamento é obrigatório.',
            'name.unique' => 'Este método de pagamento já existe.',
        ];
    }
}
