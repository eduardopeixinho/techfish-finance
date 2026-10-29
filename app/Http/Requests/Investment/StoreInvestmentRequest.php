<?php

namespace App\Http\Requests\Investment;

use App\Models\Investment\Investment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Cadastro de ativo na carteira. Cotação em R$ por unidade; quantidade em unidades inteiras.
 */
class StoreInvestmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Investment::class);
    }

    /**
     * Ticker sempre em maiúsculas (é assim que fica salvo e que o índice único compara).
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('ticker')) {
            $this->merge(['ticker' => strtoupper(trim((string) $this->input('ticker')))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'investment_type_id' => ['required', 'exists:investment_types,id'],
            'ticker' => ['required', 'string', 'max:10', $this->uniqueTickerRule()],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'current_price' => ['nullable', 'numeric', 'min:0'],
            'current_quantity' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ticker.unique' => 'Você já tem um ativo com este ticker (inclusive entre os excluídos).',
        ];
    }

    /**
     * Espelha o índice único (user_id, ticker) da tabela. Não filtra deleted_at de propósito:
     * ativos excluídos (soft delete) continuam ocupando o índice no banco.
     */
    protected function uniqueTickerRule(): Unique
    {
        return Rule::unique('investments', 'ticker')->where('user_id', $this->user()->id);
    }
}
