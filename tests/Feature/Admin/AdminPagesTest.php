<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

/**
 * Telas de Admin (perfis, permissões e usuários) no padrão <x-ui.*> + regressões encontradas na migração.
 */
class AdminPagesTest extends TestCase
{
    use HasAdminUser;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createAdminUser();
    }

    public function test_every_admin_page_renders(): void
    {
        $permission = Permission::factory()->create(['name' => 'Ver relatórios', 'module' => 'reports', 'action' => 'view']);
        $role = Role::factory()->create(['name' => 'Gestor']);
        $role->permissions()->attach($permission);
        $member = User::factory()->create(['name' => 'Fulana']);
        $member->assignRole($role);

        $pages = [
            route('admin.roles.index') => 'Gestor',
            route('admin.roles.create') => 'Relatórios',
            route('admin.roles.show', $role) => 'Fulana',
            route('admin.roles.edit', $role) => 'value="Gestor"',
            route('admin.permissions.index') => 'Ver relatórios',
            route('admin.permissions.create') => 'Visualizar',
            route('admin.permissions.show', $permission) => 'via Gestor',
            route('admin.permissions.edit', $permission) => 'value="Ver relatórios"',
            route('admin.user-roles.index') => 'Fulana',
            route('admin.user-roles.show', $member) => 'Ver relatórios',
            route('admin.user-roles.edit', $member) => 'name="roles[]"',
        ];

        foreach ($pages as $url => $expected) {
            $this->actingAs($this->admin)->get($url)
                ->assertOk()
                ->assertSee($expected, false)
                ->assertDontSee('confirm(', false);
        }
    }

    /**
     * Regressão: com nenhuma caixa marcada, o isset() impedia remover todas as permissões do perfil.
     */
    public function test_all_permissions_can_be_removed_from_a_role(): void
    {
        $role = Role::factory()->create(['name' => 'Temporário', 'color' => '#123456']);
        $role->permissions()->attach(Permission::factory()->count(2)->create());

        $this->actingAs($this->admin)->put(route('admin.roles.update', $role), [
            'name' => 'Temporário',
            'color' => '#123456',
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertCount(0, $role->fresh()->permissions);
    }

    public function test_role_and_permission_can_be_deactivated_from_the_form(): void
    {
        $role = Role::factory()->create(['name' => 'Leitor', 'color' => '#123456', 'is_active' => true]);
        $permission = Permission::factory()->create(['name' => 'Exportar', 'module' => 'reports', 'action' => 'export', 'is_active' => true]);

        $this->actingAs($this->admin)->get(route('admin.roles.edit', $role))
            ->assertSee('<input type="hidden" name="is_active" value="0">', false);

        $this->actingAs($this->admin)->put(route('admin.roles.update', $role), [
            'name' => 'Leitor', 'color' => '#123456', 'is_active' => '0',
        ])->assertRedirect();
        $this->actingAs($this->admin)->put(route('admin.permissions.update', $permission), [
            'name' => 'Exportar', 'module' => 'reports', 'action' => 'export', 'is_active' => '0',
        ])->assertRedirect();

        $this->assertFalse($role->fresh()->is_active);
        $this->assertFalse($permission->fresh()->is_active);
    }

    public function test_permission_form_has_no_phantom_display_name_field(): void
    {
        $this->actingAs($this->admin)->get(route('admin.permissions.create'))
            ->assertOk()
            ->assertDontSee('name="display_name"', false);
    }

    public function test_regular_user_cannot_open_admin_pages(): void
    {
        $this->actingAs($this->createRegularUser())
            ->get(route('admin.roles.index'))
            ->assertForbidden();
    }

    /**
     * Regressão: o slug "modulo.acao" tem índice único e a combinação repetida estourava 500.
     */
    public function test_duplicate_module_and_action_is_a_validation_error(): void
    {
        Permission::factory()->create(['name' => 'Ver relatórios', 'module' => 'reports', 'action' => 'view', 'slug' => 'reportsview']);

        $this->actingAs($this->admin)
            ->post(route('admin.permissions.store'), ['name' => 'Outra', 'module' => 'reports', 'action' => 'view'])
            ->assertSessionHasErrors('slug');

        $this->assertSame(1, Permission::where('slug', 'reportsview')->count());
    }

    /**
     * Regressão: nomes diferentes com o mesmo slug ("Gestão" / "gestao") estouravam 500.
     */
    public function test_role_with_equivalent_name_is_a_validation_error(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.roles.store'), ['name' => 'Gestão', 'color' => '#123456'])
            ->assertRedirect(route('admin.roles.index'));

        $this->actingAs($this->admin)
            ->post(route('admin.roles.store'), ['name' => 'gestao', 'color' => '#123456'])
            ->assertSessionHasErrors('slug');

        $this->assertSame(1, Role::where('slug', 'gestao')->count());
    }

    public function test_role_update_keeps_own_name_and_slug(): void
    {
        $role = Role::factory()->create(['name' => 'Leitura', 'slug' => 'leitura', 'color' => '#123456']);

        $this->actingAs($this->admin)
            ->put(route('admin.roles.update', $role), ['name' => 'Leitura', 'color' => '#654321', 'is_active' => '1'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.roles.index'));

        $this->assertSame('#654321', $role->fresh()->color);
    }

    public function test_assign_and_remove_single_role(): void
    {
        $role = Role::factory()->create(['name' => 'Convidado']);
        $member = User::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.user-roles.assign', $member), ['role_id' => $role->id])
            ->assertRedirect();
        $this->assertTrue($member->fresh()->roles->contains($role));

        $this->actingAs($this->admin)
            ->delete(route('admin.user-roles.remove', $member), ['role_id' => $role->id])
            ->assertRedirect();
        $this->assertFalse($member->fresh()->roles->contains($role));

        $this->actingAs($this->admin)
            ->post(route('admin.user-roles.assign', $member), ['role_id' => 999999])
            ->assertSessionHasErrors('role_id');
    }

    public function test_slug_errors_are_shown_in_the_forms(): void
    {
        $bag = fn (string $message) => (new ViewErrorBag)->put('default', new MessageBag(['slug' => $message]));

        $this->actingAs($this->admin)->withSession(['errors' => $bag('Já existe um perfil com nome equivalente (mesmo identificador).')])
            ->get(route('admin.roles.create'))
            ->assertSee('Já existe um perfil com nome equivalente');

        $this->actingAs($this->admin)->withSession(['errors' => $bag('Já existe uma permissão para este módulo e ação.')])
            ->get(route('admin.permissions.create'))
            ->assertSee('Já existe uma permissão para este módulo e ação.');
    }

    /**
     * Remover um perfil do usuário pede confirmação (dois cliques), como as demais exclusões.
     */
    public function test_removing_a_role_from_user_asks_for_confirmation(): void
    {
        $role = Role::factory()->create(['name' => 'Convidado']);
        $member = User::factory()->create();
        $member->assignRole($role);

        $html = $this->actingAs($this->admin)->get(route('admin.user-roles.show', $member))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<form method="POST" action="'.preg_quote(route('admin.user-roles.remove', $member), '/').'"[^>]*x-data="\\{ confirming: false.*?name="role_id" value="'.$role->id.'".*?<\\/form>/s',
            $html
        );
        $this->assertStringContainsString('Confirmar remoção', $html);
    }

    /**
     * Regressão: renomear o perfil admin (ex.: "AdmMaster") trocava o slug e tirava o acesso de admin.
     */
    public function test_renaming_system_role_keeps_slug_and_admin_access(): void
    {
        $adminRole = Role::where('slug', 'admin')->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.roles.update', $adminRole), ['name' => 'AdmMaster', 'color' => '#123456', 'is_active' => '1'])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $adminRole->refresh();
        $this->assertSame('AdmMaster', $adminRole->name);
        $this->assertSame('admin', $adminRole->slug);
        $this->assertTrue($this->admin->fresh()->isAdmin());
    }
}
