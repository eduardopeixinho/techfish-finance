{{-- Detalhe da transação. Valor em R$ via $money; data = data da transação (caixa). --}}
@php
    $sign = ['income' => '+', 'expense' => '−'][$transaction->type] ?? '';
    $amountColor = ['income' => 'text-emerald-700', 'expense' => 'text-red-700'][$transaction->type] ?? 'text-finance-base';
    $paymentLabels = [
        'cash' => 'Dinheiro', 'debit_card' => 'Cartão de débito', 'credit_card' => 'Cartão de crédito',
        'bank_transfer' => 'Transferência bancária', 'pix' => 'Pix', 'check' => 'Cheque',
    ];
@endphp

<x-finance-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header :title="$transaction->description" :kicker="$transaction->type_label.' · '.$transaction->transaction_date->format('d/m/Y')">
            <x-slot:actions>
                <x-ui.button variant="accent" :href="route('finance.transactions.edit', $transaction)">Editar</x-ui.button>
                <x-ui.button variant="secondary" :href="route('finance.transactions.index')">Transações</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.flash />

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <x-ui.card class="lg:col-span-2">
                <p class="text-sm text-ink-2">Valor</p>
                <p class="mt-1 font-serif text-4xl {{ $amountColor }}">{{ $sign }} {{ $money->format((float) $transaction->amount) }}</p>

                <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-ink-2">{{ $transaction->type === 'transfer' ? 'Conta de origem' : 'Conta' }}</dt>
                        <dd class="mt-0.5">
                            <a href="{{ route('finance.accounts.show', $transaction->account) }}" class="font-medium text-ink hover:text-finance-base">{{ $transaction->account->name }}</a>
                            @if ($transaction->account->bank) <span class="text-ink-2">· {{ $transaction->account->bank }}</span> @endif
                        </dd>
                    </div>

                    @if ($transaction->transferAccount)
                        <div>
                            <dt class="text-ink-2">Conta de destino</dt>
                            <dd class="mt-0.5">
                                <a href="{{ route('finance.accounts.show', $transaction->transferAccount) }}" class="font-medium text-ink hover:text-finance-base">{{ $transaction->transferAccount->name }}</a>
                            </dd>
                        </div>
                    @endif

                    @if ($transaction->category)
                        <div>
                            <dt class="text-ink-2">Categoria</dt>
                            <dd class="mt-0.5 inline-flex items-center gap-1.5 font-medium text-ink">
                                <span class="w-2 h-2 rounded-full" style="background-color: {{ $transaction->category->color }}" aria-hidden="true"></span>
                                <a href="{{ route('finance.categories.show', $transaction->category) }}" class="hover:text-finance-base">{{ $transaction->category->name }}</a>
                            </dd>
                        </div>
                    @endif

                    @if ($transaction->payment_method)
                        <div>
                            <dt class="text-ink-2">Forma de pagamento</dt>
                            <dd class="mt-0.5 font-medium text-ink">{{ $paymentLabels[$transaction->payment_method] ?? $transaction->payment_method }}</dd>
                        </div>
                    @endif
                </dl>

                @if ($transaction->notes)
                    <div class="mt-6 pt-6 border-t border-line">
                        <p class="text-sm text-ink-2 mb-1">Observações</p>
                        <p class="text-ink whitespace-pre-line">{{ $transaction->notes }}</p>
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card title="Registro">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-2">Criada em</dt>
                        <dd class="font-mono text-ink">{{ $transaction->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    @if ($transaction->updated_at && ! $transaction->updated_at->equalTo($transaction->created_at))
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-2">Atualizada em</dt>
                            <dd class="font-mono text-ink">{{ $transaction->updated_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-2">Código</dt>
                        <dd class="font-mono text-ink">#{{ $transaction->id }}</dd>
                    </div>
                </dl>

                <div class="mt-6 pt-6 border-t border-line">
                    <x-ui.confirm-delete :action="route('finance.transactions.destroy', $transaction)" label="Excluir transação" size="md" class="w-full" />
                </div>
            </x-ui.card>
        </div>
    </div>
</x-finance-layout>
