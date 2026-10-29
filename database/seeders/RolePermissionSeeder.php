<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar permissões
        $permissions = [
            // Administração
            ['name' => 'Gerenciar Usuários', 'slug' => 'admin.users', 'module' => 'admin', 'action' => 'manage', 'description' => 'Pode gerenciar usuários do sistema'],
            ['name' => 'Gerenciar Roles', 'slug' => 'admin.roles', 'module' => 'admin', 'action' => 'manage', 'description' => 'Pode gerenciar roles e permissões'],
            ['name' => 'Gerenciar Permissões', 'slug' => 'admin.permissions', 'module' => 'admin', 'action' => 'manage', 'description' => 'Pode gerenciar permissões do sistema'],

            // Finanças
            ['name' => 'Ler Finanças', 'slug' => 'finance.read', 'module' => 'finance', 'action' => 'read', 'description' => 'Pode visualizar dados financeiros'],
            ['name' => 'Gerenciar Finanças', 'slug' => 'finance.manage', 'module' => 'finance', 'action' => 'manage', 'description' => 'Pode gerenciar todos os aspectos financeiros'],
            ['name' => 'Criar Transações', 'slug' => 'finance.transactions.create', 'module' => 'finance', 'action' => 'create', 'description' => 'Pode criar transações'],
            ['name' => 'Editar Transações', 'slug' => 'finance.transactions.edit', 'module' => 'finance', 'action' => 'edit', 'description' => 'Pode editar transações'],
            ['name' => 'Deletar Transações', 'slug' => 'finance.transactions.delete', 'module' => 'finance', 'action' => 'delete', 'description' => 'Pode deletar transações'],
            ['name' => 'Gerenciar Contas', 'slug' => 'finance.accounts.manage', 'module' => 'finance', 'action' => 'manage', 'description' => 'Pode gerenciar contas bancárias'],
            ['name' => 'Gerenciar Categorias', 'slug' => 'finance.categories.manage', 'module' => 'finance', 'action' => 'manage', 'description' => 'Pode gerenciar categorias'],
            ['name' => 'Ver Relatórios', 'slug' => 'finance.reports.read', 'module' => 'finance', 'action' => 'read', 'description' => 'Pode visualizar relatórios financeiros'],

        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['slug' => $permission['slug']], $permission);
        }

        // Criar roles
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Administrador',
                'description' => 'Acesso total ao sistema',
                'color' => '#dc2626',
                'is_system' => true,
            ]
        );

        $financeManagerRole = Role::firstOrCreate(
            ['slug' => 'finance-manager'],
            [
                'name' => 'Gerente Financeiro',
                'description' => 'Acesso completo ao módulo financeiro',
                'color' => '#059669',
                'is_system' => true,
            ]
        );

        $financeUserRole = Role::firstOrCreate(
            ['slug' => 'finance-user'],
            [
                'name' => 'Usuário Financeiro',
                'description' => 'Acesso básico ao módulo financeiro',
                'color' => '#0d9488',
                'is_system' => false,
            ]
        );

        $userRole = Role::firstOrCreate(
            ['slug' => 'user'],
            [
                'name' => 'Usuário',
                'description' => 'Usuário padrão do sistema',
                'color' => '#6366f1',
                'is_system' => true,
            ]
        );

        // Atribuir permissões aos roles
        $adminRole->permissions()->sync(Permission::all()->pluck('id'));

        $financeManagerRole->permissions()->sync(
            Permission::where('module', 'finance')->pluck('id')
        );

        $financeUserRole->permissions()->sync(
            Permission::whereIn('slug', [
                'finance.read',
                'finance.transactions.create',
                'finance.transactions.edit',
                'finance.reports.read',
            ])->pluck('id')
        );

        $userRole->permissions()->sync([]);

        // Atribuir role admin ao primeiro usuário real (a conta de demonstração nunca é admin)
        $firstUser = User::where('email', '!=', config('demo.email'))->orderBy('id')->first();
        if ($firstUser) {
            $firstUser->assignRole($adminRole);
            $this->command->info("Role 'admin' atribuído ao usuário: {$firstUser->name}");
        }
    }
}
