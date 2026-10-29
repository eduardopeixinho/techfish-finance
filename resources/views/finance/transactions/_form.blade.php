{{--
    Campos compartilhados de criar/editar transação.

    Espera: $accounts, $incomeCategories, $expenseCategories, $paymentMethods
            $transaction (instância nova no create).
    Regras de UI (Alpine):
      - o tipo decide os campos: categoria de receita / de despesa / conta de destino;
      - campos ocultos ficam `disabled` → não são enviados (evita categoria de receita numa despesa);
      - todos os campos usam old() para não perder o que foi digitado quando a validação falha.
    Valor em R$ (sempre positivo; o tipo define se entra ou sai). Data = data da transação (caixa).
--}}
@php
    use App\Models\Finance\Transaction;

    $transaction ??= new Transaction([
        'type' => 'expense',
        'transaction_date' => now(),
        'account_id' => request('account_id'),
    ]);
    $invalid = fn (string $field): string => $errors->has($field) ? 'aria-invalid=true aria-describedby='.$field.'-error' : '';
    $selectedType = old('type', $transaction->type ?? 'expense');
@endphp

<div x-data="{ type: @js($selectedType) }" class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <fieldset class="sm:col-span-2">
        <legend class="ui-label">Tipo<span class="text-red-700" aria-hidden="true"> *</span></legend>
        <div class="grid grid-cols-3 gap-3">
            @foreach (['income' => 'text-emerald-700', 'expense' => 'text-red-700', 'transfer' => 'text-finance-base'] as $value => $color)
                <label class="flex items-center gap-2 p-3 border border-line rounded-sm bg-white cursor-pointer hover:border-finance-base/40 has-[:checked]:border-finance-base has-[:checked]:bg-finance-base/5">
                    <input type="radio" name="type" value="{{ $value }}" x-model="type" required
                           class="text-finance-base focus:ring-finance-base" @checked($selectedType === $value)>
                    <span class="font-medium text-sm {{ $color }}">{{ Transaction::TYPES[$value] }}</span>
                </label>
            @endforeach
        </div>
        @error('type') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </fieldset>

    <x-ui.field label="Descrição" for="description" required class="sm:col-span-2">
        <input type="text" id="description" name="description" value="{{ old('description', $transaction->description) }}"
               required maxlength="255" placeholder="Ex.: Mercado, Salário, Aluguel" class="ui-input" {!! $invalid('description') !!}>
    </x-ui.field>

    <x-ui.field label="Valor (R$)" for="amount" required hint="Sempre positivo: o tipo define se entra ou sai.">
        <input type="number" id="amount" name="amount" step="0.01" min="0.01" required inputmode="decimal"
               value="{{ old('amount', $transaction->amount) }}" placeholder="0,00" class="ui-input" {!! $invalid('amount') !!}>
    </x-ui.field>

    <x-ui.field label="Data" for="transaction_date" required>
        <input type="date" id="transaction_date" name="transaction_date" required
               value="{{ old('transaction_date', $transaction->transaction_date?->format('Y-m-d')) }}" class="ui-input" {!! $invalid('transaction_date') !!}>
    </x-ui.field>

    <x-ui.field label="Conta" for="account_id" required>
        <select id="account_id" name="account_id" required class="ui-input" {!! $invalid('account_id') !!}>
            <option value="">Selecione</option>
            @foreach ($accounts as $account)
                <option value="{{ $account->id }}" @selected((string) old('account_id', $transaction->account_id) === (string) $account->id)>
                    {{ $account->name }} ({{ $money->format((float) $account->current_balance) }})
                </option>
            @endforeach
        </select>
    </x-ui.field>

    {{-- Transferência: conta de destino --}}
    <x-ui.field label="Conta de destino" for="transfer_account_id" required x-show="type === 'transfer'" x-cloak>
        <select id="transfer_account_id" name="transfer_account_id" class="ui-input"
                :disabled="type !== 'transfer'" :required="type === 'transfer'" {!! $invalid('transfer_account_id') !!}>
            <option value="">Selecione</option>
            @foreach ($accounts as $account)
                <option value="{{ $account->id }}" @selected((string) old('transfer_account_id', $transaction->transfer_account_id) === (string) $account->id)>
                    {{ $account->name }}
                </option>
            @endforeach
        </select>
    </x-ui.field>

    {{-- Receita/Despesa: categoria do tipo escolhido (um select por tipo; o oculto fica desabilitado) --}}
    @foreach (['income' => $incomeCategories, 'expense' => $expenseCategories] as $categoryType => $categories)
        <x-ui.field label="Categoria" for="category_id_{{ $categoryType }}" name="category_id" hint="Opcional."
                    x-show="type === '{{ $categoryType }}'" x-cloak>
            <select id="category_id_{{ $categoryType }}" name="category_id" class="ui-input" :disabled="type !== '{{ $categoryType }}'">
                <option value="">Sem categoria</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('category_id', $transaction->category_id) === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            @if ($categories->isEmpty())
                <p class="mt-1 text-xs text-ink-2">
                    Nenhuma categoria de {{ $categoryType === 'income' ? 'receita' : 'despesa' }}.
                    <a href="{{ route('finance.categories.create') }}" class="underline underline-offset-2">Criar</a>
                </p>
            @endif
        </x-ui.field>
    @endforeach

    <x-ui.field label="Forma de pagamento" for="payment_method" hint="Opcional.">
        <select id="payment_method" name="payment_method" class="ui-input" {!! $invalid('payment_method') !!}>
            <option value="">Não informar</option>
            @foreach ($paymentMethods as $method)
                <option value="{{ $method->name }}" @selected(old('payment_method', $transaction->payment_method) === $method->name)>{{ $method->name }}</option>
            @endforeach
        </select>
    </x-ui.field>

    <x-ui.field label="Observações" for="notes" hint="Opcional." class="sm:col-span-2">
        <textarea id="notes" name="notes" rows="3" class="ui-input" placeholder="Informações adicionais" {!! $invalid('notes') !!}>{{ old('notes', $transaction->notes) }}</textarea>
    </x-ui.field>
</div>
