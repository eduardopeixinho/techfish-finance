<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rules\Unique;

/**
 * Mesmas regras do cadastro + "ativa"; nome e slug únicos ignoram a própria permissão.
 */
class UpdatePermissionRequest extends StorePermissionRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + ['is_active' => ['boolean']];
    }

    protected function uniqueRule(string $column): Unique
    {
        return parent::uniqueRule($column)->ignore($this->route('permission'));
    }
}
