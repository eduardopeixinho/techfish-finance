<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'color' => 'required|string|size:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da categoria é obrigatório.',
            'type.required' => 'O tipo é obrigatório.',
            'type.in' => 'O tipo deve ser income ou expense.',
            'color.required' => 'A cor é obrigatória.',
            'color.regex' => 'A cor deve ser um hexadecimal válido (ex: #FF5733).',
        ];
    }
}
