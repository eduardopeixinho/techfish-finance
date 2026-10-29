{{--
    Campos compartilhados de criar/editar conta.
    Espera $account (instância nova no create). "Ativa" só aparece na edição.
    Saldo inicial em R$ (competência: saldo na data de abertura no app).
    Cartão de crédito: dia de fechamento e de vencimento da fatura (só aparecem para esse tipo).
--}}
@php
    use App\Models\Finance\Account;

    $account ??= new Account(['currency' => 'BRL', 'color' => '#0B3D3A', 'initial_balance' => 0]);
    $invalid = fn (string $field): string => $errors->has($field) ? 'aria-invalid=true aria-describedby='.$field.'-error' : '';
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-6" x-data="{ type: @js(old('type', $account->type)) }">
    <x-ui.field label="Nome da conta" for="name" required class="sm:col-span-2">
        <input type="text" id="name" name="name" value="{{ old('name', $account->name) }}" required maxlength="255"
               placeholder="Ex.: Conta corrente Nubank" class="ui-input" {!! $invalid('name') !!}>
    </x-ui.field>

    <x-ui.field label="Tipo" for="type" required>
        <select id="type" name="type" required class="ui-input" x-model="type" {!! $invalid('type') !!}>
            <option value="">Selecione</option>
            @foreach (Account::TYPES as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $account->type) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </x-ui.field>

    {{-- Cartão: em qual fatura cada compra cai (compra no dia do fechamento já vai para a próxima) --}}
    <x-ui.field label="Fatura fecha no dia" for="closing_day" required x-show="type === 'credit_card'" x-cloak
                hint="Compras a partir deste dia vão para a fatura seguinte.">
        <input type="number" id="closing_day" name="closing_day" min="1" max="31" step="1" inputmode="numeric"
               value="{{ old('closing_day', $account->closing_day) }}" placeholder="Ex.: 1" class="ui-input"
               :disabled="type !== 'credit_card'" :required="type === 'credit_card'" {!! $invalid('closing_day') !!}>
    </x-ui.field>

    <x-ui.field label="Fatura vence no dia" for="due_day" required x-show="type === 'credit_card'" x-cloak>
        <input type="number" id="due_day" name="due_day" min="1" max="31" step="1" inputmode="numeric"
               value="{{ old('due_day', $account->due_day) }}" placeholder="Ex.: 8" class="ui-input"
               :disabled="type !== 'credit_card'" :required="type === 'credit_card'" {!! $invalid('due_day') !!}>
    </x-ui.field>

    <x-ui.field label="Banco / instituição" for="bank" hint="Opcional.">
        <input type="text" id="bank" name="bank" value="{{ old('bank', $account->bank) }}" maxlength="255"
               placeholder="Ex.: Nubank, Itaú, Banco do Brasil" class="ui-input" {!! $invalid('bank') !!}>
    </x-ui.field>

    <x-ui.field label="Saldo inicial (R$)" for="initial_balance" required
                hint="{{ $account->exists ? 'Alterar recalcula o saldo atual a partir das transações.' : 'Saldo na data em que você começa a usar o app.' }}">
        <input type="number" id="initial_balance" name="initial_balance" step="0.01" required inputmode="decimal"
               value="{{ old('initial_balance', $account->initial_balance) }}" class="ui-input" {!! $invalid('initial_balance') !!}>
    </x-ui.field>

    <x-ui.field label="Moeda" for="currency" required>
        <select id="currency" name="currency" required class="ui-input" {!! $invalid('currency') !!}>
            @foreach (['BRL' => 'Real (BRL)', 'USD' => 'Dólar (USD)', 'EUR' => 'Euro (EUR)'] as $value => $label)
                <option value="{{ $value }}" @selected(old('currency', $account->currency) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </x-ui.field>

    <x-ui.field label="Cor" for="color" required hint="Identifica a conta em listas e gráficos.">
        <input type="color" id="color" name="color" required value="{{ old('color', $account->color ?? '#0B3D3A') }}"
               class="h-10 w-20 rounded-sm border border-line bg-white cursor-pointer">
    </x-ui.field>

    <x-ui.field label="Descrição" for="description" hint="Opcional." class="sm:col-span-2">
        <textarea id="description" name="description" rows="3" class="ui-input"
                  placeholder="Informações adicionais sobre a conta" {!! $invalid('description') !!}>{{ old('description', $account->description) }}</textarea>
    </x-ui.field>

    @if ($account->exists)
        <label class="sm:col-span-2 flex items-center gap-2 text-sm text-ink">
            {{-- hidden antes do checkbox: desmarcado envia 0 (senão o campo some e não dá para desativar) --}}
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="rounded-sm border-line text-finance-base focus:ring-finance-base"
                   @checked(old('is_active', $account->is_active))>
            Conta ativa (aparece ao lançar transações)
        </label>
    @endif
</div>
