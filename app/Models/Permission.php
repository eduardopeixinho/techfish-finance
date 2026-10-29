<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    /**
     * Módulos sugeridos no formulário → rótulo pt-BR (o campo aceita outros valores).
     *
     * @var array<string, string>
     */
    public const MODULES = [
        'users' => 'Usuários',
        'roles' => 'Perfis',
        'permissions' => 'Permissões',
        'finance' => 'Financeiro',
        'accounts' => 'Contas',
        'categories' => 'Categorias',
        'transactions' => 'Transações',
        'reports' => 'Relatórios',
        'settings' => 'Configurações',
        'dashboard' => 'Painel',
    ];

    /**
     * Ações sugeridas no formulário → rótulo pt-BR (o campo aceita outros valores).
     *
     * @var array<string, string>
     */
    public const ACTIONS = [
        'view' => 'Visualizar',
        'create' => 'Criar',
        'edit' => 'Editar',
        'delete' => 'Excluir',
        'manage' => 'Gerenciar',
        'export' => 'Exportar',
        'import' => 'Importar',
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'module',
        'action',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relacionamento com roles
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * Scope para permissões ativas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para permissões por módulo
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope para permissões por ação
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }
}
