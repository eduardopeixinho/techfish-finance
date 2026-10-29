<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use Illuminate\Validation\Rules\Unique;

/**
 * Mesmas regras do cadastro + "ativo"; nome e slug únicos ignoram o próprio perfil.
 */
class UpdateRoleRequest extends StoreRoleRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + ['is_active' => ['boolean']];
    }

    /**
     * Perfil de sistema (ex.: admin) mantém o slug ao ser renomeado: o código o
     * identifica pelo slug (User::isAdmin → 'admin'). Renomear não pode tirar o acesso.
     */
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $role = $this->route('role');
        if ($role instanceof Role && $role->is_system) {
            $this->merge(['slug' => $role->slug]);
        }
    }

    protected function uniqueRule(string $column): Unique
    {
        return parent::uniqueRule($column)->ignore($this->route('role'));
    }
}
