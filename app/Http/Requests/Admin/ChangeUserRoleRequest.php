<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Atribui ou remove UM perfil de um usuário (botões da tela do usuário).
 */
class ChangeUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('admin.access');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role_id' => ['required', 'exists:roles,id'],
        ];
    }
}
