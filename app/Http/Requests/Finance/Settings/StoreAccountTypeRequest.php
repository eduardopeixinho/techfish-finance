<?php

namespace App\Http\Requests\Finance\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:account_types',
            'description' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do tipo de conta é obrigatório.',
            'name.unique' => 'Este tipo de conta já existe.',
        ];
    }
}
