<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Cadastro de perfil (role). O slug é derivado do nome e tem índice único:
 * nomes diferentes que geram o mesmo slug ("Gestão" / "gestao") viram erro de validação.
 */
class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('admin.access');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['slug' => Str::slug((string) $this->input('name'))]);
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
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Já existe um perfil com este nome.',
            'slug.unique' => 'Já existe um perfil com nome equivalente (mesmo identificador).',
            'color.regex' => 'A cor deve ser um hexadecimal válido (ex.: #0B3D3A).',
        ];
    }

    protected function uniqueRule(string $column): Unique
    {
        return Rule::unique('roles', $column);
    }
}
