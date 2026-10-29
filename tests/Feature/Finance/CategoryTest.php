<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\FinancialCategory;
use App\Models\Finance\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * CRUD de categorias + telas migradas para os componentes <x-ui.*>.
 */
class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_index_lists_categories_by_type_with_ui_components(): void
    {
        FinancialCategory::factory()->create(['user_id' => $this->user->id, 'type' => 'income', 'name' => 'Salário']);
        FinancialCategory::factory()->create(['user_id' => $this->user->id, 'type' => 'expense', 'name' => 'Mercado']);

        $response = $this->actingAs($this->user)->get(route('finance.categories.index'));

        $response->assertOk()
            ->assertSeeInOrder(['Receitas (1)', 'Salário', 'Despesas (1)', 'Mercado'])
            // Exclusão em dois cliques, sem confirm() nativo nem CSS de botão embutido na view
            ->assertSee('Confirmar exclusão', false)
            ->assertDontSee('confirm(', false)
            ->assertDontSee('.btn-primary {', false);
    }

    public function test_category_with_transactions_has_no_delete_button(): void
    {
        $category = FinancialCategory::factory()->create(['user_id' => $this->user->id, 'type' => 'expense']);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => Account::factory()->create(['user_id' => $this->user->id])->id,
            'category_id' => $category->id,
            'type' => 'expense',
        ]);

        $this->actingAs($this->user)->get(route('finance.categories.index'))
            ->assertOk()
            ->assertDontSee('action="'.route('finance.categories.destroy', $category).'"', false);

        $this->actingAs($this->user)->get(route('finance.categories.edit', $category))
            ->assertOk()
            ->assertSee('não pode ser excluída');
    }

    public function test_create_form_renders_and_stores_category(): void
    {
        $this->actingAs($this->user)->get(route('finance.categories.create'))
            ->assertOk()
            ->assertSee('Nova categoria')
            ->assertSee('class="ui-input"', false)
            ->assertDontSee('name="is_active"', false);

        $this->actingAs($this->user)->post(route('finance.categories.store'), [
            'type' => 'expense',
            'name' => 'Transporte',
            'color' => '#0B3D3A',
        ])->assertRedirect();

        $this->assertDatabaseHas('financial_categories', ['user_id' => $this->user->id, 'name' => 'Transporte', 'type' => 'expense']);
    }

    public function test_validation_errors_are_shown_on_fields(): void
    {
        $this->actingAs($this->user)
            ->from(route('finance.categories.create'))
            ->post(route('finance.categories.store'), ['type' => 'expense', 'name' => '', 'color' => 'azul'])
            ->assertSessionHasErrors(['name', 'color']);

        $this->actingAs($this->user)
            ->withSession(['errors' => (new ViewErrorBag)->put('default', new MessageBag(['name' => 'O nome é obrigatório.']))])
            ->get(route('finance.categories.create'))
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('O nome é obrigatório.');
    }

    public function test_edit_form_prefills_and_updates_category(): void
    {
        $category = FinancialCategory::factory()->create([
            'user_id' => $this->user->id, 'type' => 'income', 'name' => 'Freela', 'color' => '#123456', 'is_active' => true,
        ]);

        $this->actingAs($this->user)->get(route('finance.categories.edit', $category))
            ->assertOk()
            ->assertSee('value="Freela"', false)
            ->assertSee('name="is_active"', false);

        $this->actingAs($this->user)->put(route('finance.categories.update', $category), [
            'type' => 'income',
            'name' => 'Freelas',
            'color' => '#654321',
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('financial_categories', ['id' => $category->id, 'name' => 'Freelas', 'color' => '#654321']);
    }

    public function test_show_page_renders_summary(): void
    {
        $category = FinancialCategory::factory()->create(['user_id' => $this->user->id, 'type' => 'expense', 'name' => 'Lazer']);

        $this->actingAs($this->user)->get(route('finance.categories.show', $category))
            ->assertOk()
            ->assertSee('Categoria de despesa')
            ->assertSee('Nenhuma transação nesta categoria.');
    }

    public function test_empty_category_can_be_deleted(): void
    {
        $category = FinancialCategory::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->delete(route('finance.categories.destroy', $category))
            ->assertRedirect();

        $this->assertModelMissing($category);
    }

    /**
     * Regressão: checkbox desmarcado não era enviado e a categoria nunca era desativada.
     */
    public function test_unchecking_active_deactivates_category(): void
    {
        $category = FinancialCategory::factory()->create(['user_id' => $this->user->id, 'type' => 'expense', 'is_active' => true]);

        $this->actingAs($this->user)->put(route('finance.categories.update', $category), [
            'type' => 'expense',
            'name' => $category->name,
            'color' => '#0B3D3A',
            'is_active' => '0',
        ])->assertRedirect();

        $this->assertFalse($category->fresh()->is_active);
    }
}
