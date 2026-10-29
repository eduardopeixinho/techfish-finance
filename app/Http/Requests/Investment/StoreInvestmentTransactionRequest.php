<?php

namespace App\Http\Requests\Investment;

use App\Models\Investment\Investment;
use App\Models\Investment\InvestmentTransaction;
use App\Services\InvestmentPositionService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Compra/venda de um ativo. Além dos campos, garante que nenhuma venda passe da posição
 * (cotas disponíveis na data), considerando todo o histórico do ativo.
 */
class StoreInvestmentTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'investment_id' => 'required|exists:investments,id',
            'type' => 'required|in:buy,sell',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0.0001',
            'transaction_date' => 'required|date',
            'fees' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Regras que dependem do histórico do ativo (só rodam se os campos passaram).
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $positions = app(InvestmentPositionService::class);
                $editing = $this->editedTransaction();
                $investment = Investment::where('user_id', $this->user()->id)->find($this->integer('investment_id'));

                if (! $investment) {
                    return; // o controller responde 404
                }

                if (! $positions->historyStaysValid($investment, $editing, $this->candidateTransaction($editing))) {
                    $validator->errors()->add('quantity', $this->input('type') === 'sell'
                        ? 'Venda maior que a posição: na data informada não havia cotas suficientes.'
                        : 'Com essa mudança, uma venda deste ativo ficaria maior que a posição na data dela.');

                    return;
                }

                // Lançamento editado que trocou de ativo: o ativo antigo perde esse lançamento
                if ($editing && (int) $editing->investment_id !== $investment->id) {
                    $previous = Investment::where('user_id', $this->user()->id)->find($editing->investment_id);
                    if ($previous && ! $positions->historyStaysValid($previous, $editing)) {
                        $validator->errors()->add('investment_id', 'Sem este lançamento, uma venda do ativo anterior ficaria maior que a posição.');
                    }
                }
            },
        ];
    }

    /**
     * Lançamento sendo editado (null no cadastro).
     */
    protected function editedTransaction(): ?InvestmentTransaction
    {
        return null;
    }

    /**
     * Versão do lançamento com os dados do formulário, ainda não gravada.
     */
    private function candidateTransaction(?InvestmentTransaction $editing): InvestmentTransaction
    {
        $candidate = $editing ? clone $editing : new InvestmentTransaction;

        return $candidate->fill([
            'type' => $this->input('type'),
            'quantity' => $this->integer('quantity'),
            'total_amount' => round($this->integer('quantity') * (float) $this->input('price'), 2),
            'fees' => (float) $this->input('fees', 0),
            'transaction_date' => $this->input('transaction_date'),
        ]);
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'investment_id.required' => 'O investimento é obrigatório.',
            'investment_id.exists' => 'O investimento selecionado não existe.',
            'type.required' => 'O tipo é obrigatório.',
            'type.in' => 'O tipo deve ser compra ou venda.',
            'quantity.required' => 'A quantidade é obrigatória.',
            'quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'quantity.min' => 'A quantidade deve ser maior que zero.',
            'price.required' => 'O preço é obrigatório.',
            'price.numeric' => 'O preço deve ser um número.',
            'price.min' => 'O preço deve ser maior que zero.',
            'transaction_date.required' => 'A data é obrigatória.',
            'transaction_date.date' => 'A data deve ser uma data válida.',
            'fees.numeric' => 'As taxas devem ser um número.',
            'fees.min' => 'As taxas não podem ser negativas.',
            'notes.max' => 'As observações não podem ter mais que 1000 caracteres.',
        ];
    }
}
