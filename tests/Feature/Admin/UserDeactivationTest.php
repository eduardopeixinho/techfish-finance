<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

/**
 * Admin desativa/reativa usuários: desativado não entra (login, sessão, API), mas os dados ficam.
 */
class UserDeactivationTest extends TestCase
{
    use HasAdminUser;
    use RefreshDatabase;

    public function test_admin_deactivates_and_reactivates_user(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createRegularUser();
        $user->createToken('script');

        $this->actingAs($admin)
            ->patch(route('admin.user-roles.deactivate', $user))
            ->assertRedirect()
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue($user->isDeactivated());
        $this->assertCount(0, $user->tokens);
        $this->assertDatabaseHas('users', ['id' => $user->id]);

        $this->actingAs($admin)
            ->patch(route('admin.user-roles.activate', $user))
            ->assertRedirect();

        $this->assertFalse($user->fresh()->isDeactivated());
    }

    public function test_deactivated_user_is_logged_out_on_next_request(): void
    {
        $user = $this->createRegularUser();
        $user->forceFill(['deactivated_at' => now()])->save();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Sua conta foi desativada. Fale com o administrador.');

        $this->assertGuest();
    }

    public function test_deactivated_user_cannot_log_in_with_google(): void
    {
        $user = User::factory()->create(['email' => 'fulano@gmail.com', 'google_id' => 'google-123']);
        $user->forceFill(['deactivated_at' => now()])->save();

        $googleUser = (new SocialiteUser)->map([
            'id' => 'google-123',
            'nickname' => null,
            'name' => 'Fulano',
            'email' => 'fulano@gmail.com',
            'avatar' => null,
        ]);
        Socialite::shouldReceive('driver->user')->andReturn($googleUser);

        $this->get(route('google.callback'))
            ->assertRedirect('/login')
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_deactivated_user_api_token_is_rejected(): void
    {
        $user = $this->createRegularUser();
        $token = $user->createToken('script')->plainTextToken;
        $user->forceFill(['deactivated_at' => now()])->save();

        $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
    }

    public function test_admin_cannot_deactivate_self_or_another_admin(): void
    {
        $admin = $this->createAdminUser();
        $otherAdmin = User::factory()->create();
        $otherAdmin->assignRole(Role::where('slug', 'admin')->firstOrFail());

        $this->actingAs($admin)->patch(route('admin.user-roles.deactivate', $admin))->assertForbidden();
        $this->actingAs($admin)->patch(route('admin.user-roles.deactivate', $otherAdmin))->assertForbidden();

        $this->assertFalse($admin->fresh()->isDeactivated());
        $this->assertFalse($otherAdmin->fresh()->isDeactivated());
    }

    public function test_non_admin_cannot_deactivate(): void
    {
        $user = $this->createRegularUser();
        $target = $this->createRegularUser();

        $this->actingAs($user)->patch(route('admin.user-roles.deactivate', $target))->assertForbidden();

        $this->assertFalse($target->fresh()->isDeactivated());
    }

    public function test_user_list_shows_status_and_actions_with_confirmation(): void
    {
        $admin = $this->createAdminUser();
        $active = User::factory()->create(['name' => 'Ativa']);
        $inactive = User::factory()->create(['name' => 'Inativa']);
        $inactive->forceFill(['deactivated_at' => now()])->save();

        $html = $this->actingAs($admin)->get(route('admin.user-roles.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Desativado', $html);
        $this->assertStringContainsString(route('admin.user-roles.activate', $inactive), $html);
        // Desativar pede confirmação (2 cliques) e usa PATCH
        $this->assertMatchesRegularExpression(
            '/<form method="POST" action="'.preg_quote(route('admin.user-roles.deactivate', $active), '/').'"[^>]*x-data="\\{ confirming: false.*?name="_method" value="PATCH".*?<\\/form>/s',
            $html
        );
        $this->assertStringNotContainsString(route('admin.user-roles.deactivate', $admin), $html);

        $this->actingAs($admin)->get(route('admin.user-roles.show', $inactive))
            ->assertOk()
            ->assertSee('Conta desativada em');
    }
}
