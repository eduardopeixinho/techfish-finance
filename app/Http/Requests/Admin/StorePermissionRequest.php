<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Cadastro de permissão. O slug é derivado de módulo + ação e tem índice único:
 * repetir a combinação vira erro de validação (antes estourava 500 no banco).
 */
class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('admin.access');
    }

    protected function prepareForValidation(): void
    {
        // Mesma derivação usada desde o início (mantém os slugs já gravados compatíveis)
        $this->merge(['slug' => Str::slug($this->input('module').'.'.$this->input('action'))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', $this->uniqueRule('name')],
            'slug' => ['required', 'string', $this->uniqueRule('slug')],
            'description' => ['nullable', 'string', 'max:500'],
            'module' => ['required', 'string', 'max:100'],
            'action' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Já existe uma permissão com este nome.',
            'slug.unique' => 'Já existe uma permissão para este módulo e ação.',
        ];
    }

    protected function uniqueRule(string $column): Unique
    {
        return Rule::unique('permissions', $column);
    }
}
