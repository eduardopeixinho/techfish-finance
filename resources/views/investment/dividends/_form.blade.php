{{--
    Campos compartilhados de criar/editar provento.
    Espera $investments, $dividend (instância nova no create) e, opcionalmente, $selectedInvestment.
    Prévia (Alpine) replica o cálculo salvo pelo controller (R$):
      bruto = valor por cota × cotas; líquido = bruto − IR retido.
    Datas: "data com" (record_date) define quem tem direito; pagamento = competência em caixa.
--}}
@php
    use App\Models\Investment\InvestmentDividend;

    $dividend ??= new InvestmentDividend(['type' => 'dividend', 'payment_date' => now()]);
    $invalid = fn (string $field): string => $errors->has($field) ? 'aria-invalid=true aria-describedby='.$field.'-error' : '';
    $initial = [
        'perShare' => old('amount_per_share', $dividend->amount_per_share),
        'quantity' => old('quantity_owned', $dividend->quantity_owned ?? ($selectedInvestment ?? null)?->current_quantity),
        'tax' => old('tax_withheld', $dividend->tax_withheld),
    ];
@endphp

<div x-data="{
        ...@js($initial),
        brl(value) { return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0) },
        get gross() { return (parseFloat(this.perShare) || 0) * (parseInt(this.quantity) || 0) },
        get net() { return this.gross - (parseFloat(this.tax) || 0) },
     }"
     class="grid grid-cols-1 sm:grid-cols-2 gap-6">

    <x-ui.field label="Ativo" for="investment_id" required>
        <select id="investment_id" name="investment_id" required class="ui-input" {!! $invalid('investment_id') !!}
                @change="const q = $event.target.selectedOptions[0]?.dataset.quantity; if (q && ! quantity) quantity = q">
            <option value="">Selecione</option>
            @foreach ($investments as $investment)
                <option value="{{ $investment->id }}" data-quantity="{{ (int) $investment->current_quantity }}"
                        @selected((string) old('investment_id', $dividend->investment_id ?? ($selectedInvestment ?? null)?->id) === (string) $investment->id)>
                    {{ $investment->ticker }} — {{ $investment->name }}
                </option>
            @endforeach
        </select>
        @if ($investments->isEmpty())
            <p class="mt-1 text-xs text-ink-2">Nenhum ativo de um tipo que paga proventos.</p>
        @endif
    </x-ui.field>

    <x-ui.field label="Tipo" for="type" required>
        <select id="type" name="type" required class="ui-input" {!! $invalid('type') !!}>
            @foreach (InvestmentDividend::TYPES as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $dividend->type) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </x-ui.field>

    <x-ui.field label="Valor por cota (R$)" for="amount_per_share" required hint="Até 6 casas decimais, como informado pela empresa/fundo.">
        <input type="number" id="amount_per_share" name="amount_per_share" x-model="perShare" step="0.000001" min="0" required inputmode="decimal"
               class="ui-input" {!! $invalid('amount_per_share') !!}>
    </x-ui.field>

    <x-ui.field label="Cotas na data com" for="quantity_owned" required>
        <input type="number" id="quantity_owned" name="quantity_owned" x-model="quantity" step="1" min="1" required inputmode="numeric"
               class="ui-input" {!! $invalid('quantity_owned') !!}>
    </x-ui.field>

    <x-ui.field label="Data com" for="record_date" required hint="Quem tinha as cotas nesta data recebe.">
        <input type="date" id="record_date" name="record_date" required
               value="{{ old('record_date', $dividend->record_date?->format('Y-m-d')) }}" class="ui-input" {!! $invalid('record_date') !!}>
    </x-ui.field>

    <x-ui.field label="Data de pagamento" for="payment_date" required>
        <input type="date" id="payment_date" name="payment_date" required
               value="{{ old('payment_date', $dividend->payment_date?->format('Y-m-d')) }}" class="ui-input" {!! $invalid('payment_date') !!}>
    </x-ui.field>

    <x-ui.field label="IR retido (R$)" for="tax_withheld" hint="JCP costuma ter 15% retido na fonte.">
        <input type="number" id="tax_withheld" name="tax_withheld" x-model="tax" step="0.01" min="0" inputmode="decimal"
               class="ui-input" {!! $invalid('tax_withheld') !!}>
    </x-ui.field>

    <x-ui.field label="Observações" for="notes" hint="Opcional." class="sm:col-span-2">
        <textarea id="notes" name="notes" rows="2" maxlength="1000" class="ui-input" {!! $invalid('notes') !!}>{{ old('notes', $dividend->notes) }}</textarea>
    </x-ui.field>

    {{-- Prévia do cálculo --}}
    <div class="sm:col-span-2 rounded-sm border border-line bg-paper/60 p-4 text-sm" x-show="gross > 0" x-cloak aria-live="polite">
        <dl class="grid grid-cols-3 gap-4">
            <div>
                <dt class="text-ink-2">Bruto</dt>
                <dd class="font-mono font-medium text-ink" x-text="brl(gross)"></dd>
            </div>
            <div>
                <dt class="text-ink-2">IR retido</dt>
                <dd class="font-mono text-red-700" x-text="brl(tax)"></dd>
            </div>
            <div>
                <dt class="text-ink-2">Líquido</dt>
                <dd class="font-mono font-medium text-emerald-700" x-text="brl(net)"></dd>
            </div>
        </dl>
    </div>
</div>
