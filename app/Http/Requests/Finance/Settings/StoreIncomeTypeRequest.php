<?php

namespace App\Http\Requests\Finance\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncomeTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:income_types',
            'description' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do tipo de receita é obrigatório.',
            'name.unique' => 'Este tipo de receita já existe.',
        ];
    }
}
