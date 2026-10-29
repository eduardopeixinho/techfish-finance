<?php

namespace App\Http\Requests\Investment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvestmentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:investment_types,code,'.$this->route('type')?->id,
            'description' => 'nullable|string',
            'has_dividends' => 'boolean',
            'color' => 'required|string|size:7|regex:/^#[0-9A-Fa-f]{6}$/',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do tipo de investimento é obrigatório.',
            'code.required' => 'O código do tipo é obrigatório.',
            'code.unique' => 'Este código já está em uso.',
            'color.required' => 'A cor é obrigatória.',
            'color.regex' => 'A cor deve ser um hexadecimal válido (ex: #FF5733).',
        ];
    }
}
