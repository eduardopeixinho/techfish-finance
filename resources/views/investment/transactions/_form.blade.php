{{--
    Campos compartilhados de criar/editar compra/venda de ativo.
    Espera $investments, $transaction (instância nova no create) e, opcionalmente, $selectedInvestment.
    Prévia (Alpine) replica o cálculo salvo pelo controller: total = quantidade × preço; líquido = total + custos (R$).
--}}
@php
    $transaction ??= new \App\Models\Investment\InvestmentTransaction(['type' => 'buy', 'transaction_date' => now()]);
    $invalid = fn (string $field): string => $errors->has($field) ? 'aria-invalid=true aria-describedby='.$field.'-error' : '';
    $initial = [
        'type' => old('type', $transaction->type ?? 'buy'),
        'quantity' => old('quantity', $transaction->quantity),
        'price' => old('price', $transaction->price ?? ($selectedInvestment ?? null)?->current_price),
        'fees' => old('fees', $transaction->fees),
    ];
@endphp

<div x-data="{
        ...@js($initial),
        brl(value) { return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0) },
        get total() { return (parseInt(this.quantity) || 0) * (parseFloat(this.price) || 0) },
        get net() { return this.total + (parseFloat(this.fees) || 0) },
     }"
     class="grid grid-cols-1 sm:grid-cols-2 gap-6">

    <x-ui.field label="Ativo" for="investment_id" required class="sm:col-span-2">
        <select id="investment_id" name="investment_id" required class="ui-input" {!! $invalid('investment_id') !!}
                @change="const p = $event.target.selectedOptions[0]?.dataset.price; if (p && ! price) price = p">
            <option value="">Selecione</option>
            @foreach ($investments as $investment)
                <option value="{{ $investment->id }}" data-price="{{ $investment->current_price }}"
                        @selected((string) old('investment_id', $transaction->investment_id ?? ($selectedInvestment ?? null)?->id) === (string) $investment->id)>
                    {{ $investment->ticker }} — {{ $investment->name }}
                </option>
            @endforeach
        </select>
    </x-ui.field>

    <fieldset class="sm:col-span-2">
        <legend class="ui-label">Operação<span class="text-red-700" aria-hidden="true"> *</span></legend>
        <div class="grid grid-cols-2 gap-3">
            @foreach (['buy' => ['Compra', 'text-emerald-700'], 'sell' => ['Venda', 'text-red-700']] as $value => [$label, $color])
                <label class="flex items-center gap-2 p-3 border border-line rounded-sm bg-white cursor-pointer hover:border-finance-base/40 has-[:checked]:border-finance-base has-[:checked]:bg-finance-base/5">
                    <input type="radio" name="type" value="{{ $value }}" x-model="type" required class="text-finance-base focus:ring-finance-base"
                           @checked($initial['type'] === $value)>
                    <span class="font-medium text-sm {{ $color }}">{{ $label }}</span>
                </label>
            @endforeach
        </div>
        @error('type') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </fieldset>

    <x-ui.field label="Quantidade" for="quantity" required>
        <input type="number" id="quantity" name="quantity" x-model="quantity" step="1" min="1" required inputmode="numeric"
               class="ui-input" {!! $invalid('quantity') !!}>
    </x-ui.field>

    <x-ui.field label="Preço por unidade (R$)" for="price" required>
        <input type="number" id="price" name="price" x-model="price" step="0.0001" min="0.0001" required inputmode="decimal"
               class="ui-input" {!! $invalid('price') !!}>
    </x-ui.field>

    <x-ui.field label="Custos (R$)" for="fees" hint="Corretagem, emolumentos. Opcional.">
        <input type="number" id="fees" name="fees" x-model="fees" step="0.01" min="0" inputmode="decimal"
               class="ui-input" {!! $invalid('fees') !!}>
    </x-ui.field>

    <x-ui.field label="Data" for="transaction_date" required>
        <input type="date" id="transaction_date" name="transaction_date" required
               value="{{ old('transaction_date', $transaction->transaction_date?->format('Y-m-d')) }}" class="ui-input" {!! $invalid('transaction_date') !!}>
    </x-ui.field>

    <x-ui.field label="Observações" for="notes" hint="Opcional." class="sm:col-span-2">
        <textarea id="notes" name="notes" rows="3" maxlength="1000" class="ui-input" {!! $invalid('notes') !!}>{{ old('notes', $transaction->notes) }}</textarea>
    </x-ui.field>

    {{-- Prévia do cálculo --}}
    <div class="sm:col-span-2 rounded-sm border border-line bg-paper/60 p-4 text-sm" x-show="total > 0" x-cloak aria-live="polite">
        <dl class="grid grid-cols-3 gap-4">
            <div>
                <dt class="text-ink-2">Total da operação</dt>
                <dd class="font-mono font-medium text-ink" x-text="brl(total)"></dd>
            </div>
            <div>
                <dt class="text-ink-2">Custos</dt>
                <dd class="font-mono text-ink" x-text="brl(fees)"></dd>
            </div>
            <div>
                <dt class="text-ink-2">Total com custos</dt>
                <dd class="font-mono font-medium" :class="type === 'buy' ? 'text-red-700' : 'text-emerald-700'" x-text="brl(net)"></dd>
            </div>
        </dl>
    </div>
</div>
