<?php

namespace Tests\Feature\Finance\Settings;

use App\Models\Finance\Settings\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class PaymentMethodTest extends TestCase
{
    use HasAdminUser, RefreshDatabase;

    public function test_admin_can_list_payment_methods(): void
    {
        $admin = $this->createAdminUser();
        PaymentMethod::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('settings.payment-methods.index'));

        $response->assertStatus(200);
        $response->assertViewIs('finance.settings.payment-methods.index');
        $response->assertViewHas('paymentMethods');
    }

    public function test_regular_user_cannot_list_payment_methods(): void
    {
        $user = $this->createRegularUser();

        $response = $this->actingAs($user)->get(route('settings.payment-methods.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_create_payment_method(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post(route('settings.payment-methods.store'), [
            'name' => 'PIX',
            'description' => 'Pagamento via PIX',
            'active' => true,
        ]);

        $response->assertRedirect(route('settings.payment-methods.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payment_methods', [
            'name' => 'PIX',
            'description' => 'Pagamento via PIX',
            'active' => true,
        ]);
    }

    public function test_admin_cannot_create_payment_method_with_duplicate_name(): void
    {
        $admin = $this->createAdminUser();
        PaymentMethod::factory()->create(['name' => 'PIX']);

        $response = $this->actingAs($admin)->post(route('settings.payment-methods.store'), [
            'name' => 'PIX',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_admin_can_update_payment_method(): void
    {
        $admin = $this->createAdminUser();
        $paymentMethod = PaymentMethod::factory()->create();

        $response = $this->actingAs($admin)->put(route('settings.payment-methods.update', $paymentMethod), [
            'name' => 'PIX Atualizado',
            'description' => 'Descrição atualizada',
            'active' => false,
        ]);

        $response->assertRedirect(route('settings.payment-methods.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payment_methods', [
            'id' => $paymentMethod->id,
            'name' => 'PIX Atualizado',
            'active' => false,
        ]);
    }

    public function test_admin_can_delete_payment_method(): void
    {
        $admin = $this->createAdminUser();
        $paymentMethod = PaymentMethod::factory()->create();

        $response = $this->actingAs($admin)->delete(route('settings.payment-methods.destroy', $paymentMethod));

        $response->assertRedirect(route('settings.payment-methods.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('payment_methods', ['id' => $paymentMethod->id]);
    }

    public function test_unauthenticated_user_cannot_access_payment_methods(): void
    {
        $response = $this->get(route('settings.payment-methods.index'));

        $response->assertRedirect(route('login'));
    }
}
