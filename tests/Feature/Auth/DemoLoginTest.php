<?php

namespace Tests\Feature\Auth;

use App\Models\Finance\Account;
use App\Models\Finance\Transaction;
use App\Models\Investment\Investment;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Modo demonstração (config/demo.php + DemoSeeder).
 */
class DemoLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_login_is_404_when_disabled(): void
    {
        $this->seed(DemoSeeder::class);

        $this->post(route('demo.login'))->assertNotFound();
        $this->assertGuest();
    }

    public function test_demo_login_enters_demo_account(): void
    {
        config(['demo.enabled' => true]);
        $this->seed(DemoSeeder::class);

        $this->post(route('demo.login'))->assertRedirect(route('finance.dashboard'));

        $this->assertAuthenticatedAs(User::where('email', config('demo.email'))->first());
    }

    public function test_demo_login_without_seed_explains_how_to_create_it(): void
    {
        config(['demo.enabled' => true]);

        $this->post(route('demo.login'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_demo_seeder_creates_consistent_fictional_data_and_is_idempotent(): void
    {
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $demo = User::where('email', config('demo.email'))->firstOrFail();

        $this->assertSame(1, User::where('email', config('demo.email'))->count());
        $this->assertSame(3, Account::where('user_id', $demo->id)->count());
        $this->assertGreaterThan(40, Transaction::where('user_id', $demo->id)->count());
        $this->assertSame(4, Investment::where('user_id', $demo->id)->count());

        // Saldo recalculado a partir das transações (não o inicial)
        $checking = Account::where('user_id', $demo->id)->where('type', 'checking')->firstOrFail();
        $this->assertNotEquals((float) $checking->initial_balance, (float) $checking->current_balance);

        $this->actingAs($demo)->get(route('finance.bills.index'))->assertOk();
        $this->actingAs($demo)->get(route('investment.dashboard'))->assertOk();
    }

    public function test_demo_account_cannot_be_edited_or_deleted(): void
    {
        $this->seed(DemoSeeder::class);
        $demo = User::where('email', config('demo.email'))->firstOrFail();

        $this->actingAs($demo)->patch(route('profile.update'), ['name' => 'Hack', 'email' => 'x@example.com'])->assertForbidden();
        $this->actingAs($demo)->delete(route('profile.destroy'), ['email_confirmation' => $demo->email])->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $demo->id, 'name' => config('demo.name')]);
    }

    public function test_demo_account_never_becomes_admin(): void
    {
        $this->seed(DemoSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->assertFalse(User::where('email', config('demo.email'))->firstOrFail()->isAdmin());
    }

    /**
     * Desativar a conta demo derrubaria o botão "Ver demonstração" para todos os visitantes.
     */
    public function test_admin_cannot_deactivate_demo_account(): void
    {
        $this->seed(DemoSeeder::class);
        $demo = User::where('email', config('demo.email'))->firstOrFail();
        $admin = User::factory()->create();
        $admin->assignRole(\App\Models\Role::factory()->admin()->create());

        $this->actingAs($admin)->patch(route('admin.user-roles.deactivate', $demo))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.user-roles.index'))
            ->assertOk()
            ->assertDontSee(route('admin.user-roles.deactivate', $demo), false);

        $this->assertFalse($demo->fresh()->isDeactivated());
    }

    public function test_deactivated_demo_account_shows_message_instead_of_login_loop(): void
    {
        config(['demo.enabled' => true]);
        $this->seed(DemoSeeder::class);
        User::where('email', config('demo.email'))->update(['deactivated_at' => now()]);

        $this->post(route('demo.login'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
        $this->assertGuest();
    }
}
