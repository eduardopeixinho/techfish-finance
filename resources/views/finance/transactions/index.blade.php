{{-- Lista de transações com filtros (GET). Valores em R$ via $money. --}}
@php
    use App\Models\Finance\Transaction;

    $hasFilters = request()->hasAny(['search', 'type', 'account_id', 'category_id', 'start_date', 'end_date'])
        && collect(request()->only(['search', 'type', 'account_id', 'category_id', 'start_date', 'end_date']))->filter()->isNotEmpty();
@endphp

<x-finance-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header title="Transações" kicker="Finanças">
            <x-slot:actions>
                <x-ui.button :href="route('finance.transactions.create')">Nova transação</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.flash />

        {{-- Filtros (abertos por padrão quando há filtro ativo) --}}
        <details class="mb-6 group" @if ($hasFilters) open @endif>
            <summary class="inline-flex items-center gap-2 cursor-pointer select-none text-sm font-medium text-finance-base mb-3">
                <span class="transition-transform group-open:rotate-90" aria-hidden="true">▸</span>
                Filtros
                @if ($hasFilters) <x-ui.badge tone="transfer">ativos</x-ui.badge> @endif
            </summary>

            <x-ui.card>
                <form method="GET" action="{{ route('finance.transactions.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                    <x-ui.field label="Buscar" for="search" class="lg:col-span-2">
                        <input type="search" id="search" name="search" value="{{ request('search') }}" placeholder="Descrição ou observações" class="ui-input">
                    </x-ui.field>

                    <x-ui.field label="Tipo" for="type">
                        <select id="type" name="type" class="ui-input">
                            <option value="">Todos</option>
                            @foreach (Transaction::TYPES as $value => $label)
                                <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Conta" for="account_id">
                        <select id="account_id" name="account_id" class="ui-input">
                            <option value="">Todas</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}" @selected((string) request('account_id') === (string) $account->id)>{{ $account->name }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Categoria" for="category_id" class="lg:col-span-2">
                        <select id="category_id" name="category_id" class="ui-input">
                            <option value="">Todas</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="De" for="start_date">
                        <input type="date" id="start_date" name="start_date" value="{{ request('start_date') }}" class="ui-input">
                    </x-ui.field>

                    <x-ui.field label="Até" for="end_date">
                        <input type="date" id="end_date" name="end_date" value="{{ request('end_date') }}" class="ui-input">
                    </x-ui.field>

                    <div class="sm:col-span-2 lg:col-span-4 flex items-end gap-2">
                        <x-ui.button type="submit">Filtrar</x-ui.button>
                        <x-ui.button variant="secondary" :href="route('finance.transactions.index')">Limpar</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </details>

        <x-ui.card class="!p-0 overflow-hidden">
            @if ($transactions->isEmpty())
                <div class="px-6 py-12 text-center">
                    <p class="text-ink-2 mb-4">{{ $hasFilters ? 'Nenhuma transação com esses filtros.' : 'Nenhuma transação lançada ainda.' }}</p>
                    @if ($hasFilters)
                        <x-ui.button variant="secondary" :href="route('finance.transactions.index')">Limpar filtros</x-ui.button>
                    @else
                        <x-ui.button :href="route('finance.transactions.create')">Lançar a primeira</x-ui.button>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-line bg-paper/60 text-left text-xs uppercase tracking-wider text-ink-2">
                            <tr>
                                <th class="px-6 py-3 font-medium">Data</th>
                                <th class="px-6 py-3 font-medium">Descrição</th>
                                <th class="px-6 py-3 font-medium">Categoria</th>
                                <th class="px-6 py-3 font-medium">Conta</th>
                                <th class="px-6 py-3 font-medium text-right">Valor</th>
                                <th class="px-6 py-3"><span class="sr-only">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($transactions as $transaction)
                                @php
                                    $sign = ['income' => '+', 'expense' => '−'][$transaction->type] ?? '';
                                    $amountColor = ['income' => 'text-emerald-700', 'expense' => 'text-red-700'][$transaction->type] ?? 'text-finance-base';
                                @endphp
                                <tr class="hover:bg-white/60">
                                    <td class="px-6 py-3 whitespace-nowrap font-mono text-ink-2">{{ $transaction->formatted_date }}</td>
                                    <td class="px-6 py-3">
                                        <a href="{{ route('finance.transactions.show', $transaction) }}" class="font-medium text-ink hover:text-finance-base">{{ $transaction->description }}</a>
                                        @if ($transaction->type === 'transfer')
                                            <x-ui.badge tone="transfer" class="ml-1">Transferência</x-ui.badge>
                                        @endif
                                        @if ($transaction->notes)
                                            <span class="block text-ink-2">{{ Str::limit($transaction->notes, 50) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap">
                                        @if ($transaction->category)
                                            <span class="inline-flex items-center gap-1.5 text-ink">
                                                <span class="w-2 h-2 rounded-full" style="background-color: {{ $transaction->category->color }}" aria-hidden="true"></span>
                                                {{ $transaction->category->name }}
                                            </span>
                                        @else
                                            <span class="text-ink-2">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-ink">
                                        {{ $transaction->account->name }}
                                        @if ($transaction->type === 'transfer' && $transaction->transferAccount)
                                            <span class="block text-xs text-ink-2">→ {{ $transaction->transferAccount->name }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right font-medium {{ $amountColor }}">
                                        {{ $sign }} {{ $money->format((float) $transaction->amount) }}
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <x-ui.button variant="accent" size="sm" :href="route('finance.transactions.edit', $transaction)">Editar</x-ui.button>
                                            <x-ui.confirm-delete :action="route('finance.transactions.destroy', $transaction)" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($transactions->hasPages())
                    <div class="px-6 py-3 border-t border-line">{{ $transactions->links() }}</div>
                @endif
            @endif
        </x-ui.card>
    </div>
</x-finance-layout>
