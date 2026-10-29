<?php

namespace Tests\Feature\Finance\Settings;

use App\Models\Finance\Settings\AccountType;
use App\Models\Finance\Settings\IncomeType;
use App\Models\Finance\Settings\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

/**
 * Telas genéricas dos catálogos (finance/settings/_crud + App\Support\SettingsCatalog).
 */
class SettingsCatalogViewsTest extends TestCase
{
    use HasAdminUser;
    use RefreshDatabase;

    /**
     * @return array<string, array{0: class-string, 1: string, 2: string}>
     */
    public static function catalogs(): array
    {
        return [
            'tipos de conta' => [AccountType::class, 'settings.account-types', 'Tipos de conta'],
            'tipos de receita' => [IncomeType::class, 'settings.income-types', 'Tipos de receita'],
            'formas de pagamento' => [PaymentMethod::class, 'settings.payment-methods', 'Formas de pagamento'],
        ];
    }

    /**
     * @param  class-string<Model>  $model
     */
    #[DataProvider('catalogs')]
    public function test_admin_can_open_every_catalog_page(string $model, string $route, string $title): void
    {
        $admin = $this->createAdminUser();
        $item = $model::factory()->create(['name' => 'Item de teste', 'description' => 'Descrição de teste']);

        $this->actingAs($admin)->get(route($route.'.index'))
            ->assertOk()->assertSee($title)->assertSee('Item de teste')->assertSee('Confirmar exclusão', false);

        $this->actingAs($admin)->get(route($route.'.create'))
            ->assertOk()->assertSee('name="name"', false);

        $this->actingAs($admin)->get(route($route.'.edit', $item))
            ->assertOk()->assertSee('value="Item de teste"', false);

        $this->actingAs($admin)->get(route($route.'.show', $item))
            ->assertOk()->assertSee('Descrição de teste');
    }

    public function test_settings_index_lists_the_three_catalogs(): void
    {
        $this->actingAs($this->createAdminUser())
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSeeInOrder(['Tipos de conta', 'Tipos de receita', 'Formas de pagamento']);
    }

    /**
     * Regressão: checkbox desmarcado não era enviado e a forma de pagamento nunca era desativada.
     */
    public function test_payment_method_can_be_deactivated(): void
    {
        $method = PaymentMethod::factory()->create(['name' => 'Boleto', 'active' => true]);

        $this->actingAs($this->createAdminUser())
            ->put(route('settings.payment-methods.update', $method), ['name' => 'Boleto', 'active' => '0'])
            ->assertRedirect();

        $this->assertFalse((bool) $method->fresh()->active);
    }

    public function test_regular_user_cannot_open_catalog_pages(): void
    {
        $this->actingAs($this->createRegularUser())
            ->get(route('settings.payment-methods.create'))
            ->assertForbidden();
    }
}
