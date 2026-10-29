{{--
    Campos compartilhados de criar/editar investimento (ativo da carteira).
    Espera $investmentTypes e $investment (instância nova no create).
    Preço em R$ por unidade (cotação); total investido e resultado são calculados pelas transações.
--}}
@php
    $investment ??= new \App\Models\Investment\Investment;
    $invalid = fn (string $field): string => $errors->has($field) ? 'aria-invalid=true aria-describedby='.$field.'-error' : '';
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <x-ui.field label="Tipo" for="investment_type_id" required>
        <select id="investment_type_id" name="investment_type_id" required class="ui-input" {!! $invalid('investment_type_id') !!}>
            <option value="">Selecione</option>
            @foreach ($investmentTypes as $type)
                <option value="{{ $type->id }}" @selected((string) old('investment_type_id', $investment->investment_type_id) === (string) $type->id)>{{ $type->name }}</option>
            @endforeach
        </select>
    </x-ui.field>

    <x-ui.field label="Ticker" for="ticker" required hint="Código na bolsa. Salvo em maiúsculas.">
        <input type="text" id="ticker" name="ticker" value="{{ old('ticker', $investment->ticker) }}" required maxlength="10"
               placeholder="Ex.: VALE3, MXRF11" class="ui-input font-mono uppercase" autocapitalize="characters" {!! $invalid('ticker') !!}>
    </x-ui.field>

    <x-ui.field label="Nome" for="name" required class="sm:col-span-2">
        <input type="text" id="name" name="name" value="{{ old('name', $investment->name) }}" required maxlength="255"
               placeholder="Ex.: Vale S.A., Maxi Renda FII" class="ui-input" {!! $invalid('name') !!}>
    </x-ui.field>

    <x-ui.field label="Cotação atual (R$)" for="current_price" hint="Preço de uma unidade hoje. Usado no valor atual.">
        <input type="number" id="current_price" name="current_price" step="0.01" min="0" inputmode="decimal"
               value="{{ old('current_price', $investment->current_price) }}" placeholder="0,00" class="ui-input" {!! $invalid('current_price') !!}>
    </x-ui.field>

    @php($hasHistory = $investment->exists && $investment->transactions()->exists())
    <x-ui.field label="Quantidade" for="current_quantity"
                hint="{{ $hasHistory ? 'Calculada pelas compras e vendas deste ativo.' : 'Sem compras lançadas, vale o que você digitar. Ao lançar a primeira compra, passa a ser calculada.' }}">
        <input type="number" id="current_quantity" name="current_quantity" step="1" min="0" inputmode="numeric"
               value="{{ old('current_quantity', $investment->current_quantity) }}" placeholder="0" class="ui-input" {!! $invalid('current_quantity') !!}
               @if ($hasHistory) readonly aria-readonly="true" @endif>
    </x-ui.field>

    <x-ui.field label="Descrição" for="description" hint="Opcional." class="sm:col-span-2">
        <textarea id="description" name="description" rows="3" class="ui-input" placeholder="Tese, observações..." {!! $invalid('description') !!}>{{ old('description', $investment->description) }}</textarea>
    </x-ui.field>
</div>
