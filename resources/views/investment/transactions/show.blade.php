{{-- Detalhe de uma compra/venda. Preço unitário com 4 casas é cotação (fica visível). Totais via $money. --}}
@php $isBuy = $transaction->type === 'buy'; @endphp

<x-finance-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header :title="($isBuy ? 'Compra' : 'Venda').' de '.($transaction->investment?->ticker ?? 'ativo')" :kicker="'Investimentos · '.$transaction->transaction_date->format('d/m/Y')">
            <x-slot:actions>
                <x-ui.button variant="accent" :href="route('investment.transactions.edit', $transaction)">Editar</x-ui.button>
                <x-ui.button variant="secondary" :href="route('investment.transactions.index')">Compras e vendas</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.flash />

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <x-ui.card class="lg:col-span-2">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-ink-2">Total da operação</p>
                        <p class="mt-1 font-serif text-3xl text-ink">{{ $money->format((float) $transaction->total_amount) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-ink-2">Total com custos</p>
                        <p class="mt-1 font-serif text-3xl {{ $isBuy ? 'text-red-700' : 'text-emerald-700' }}">{{ $money->format((float) $transaction->net_amount) }}</p>
                    </div>
                </div>

                <dl class="mt-6 pt-6 border-t border-line grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-ink-2">Quantidade</dt>
                        <dd class="mt-0.5 font-mono text-ink">{{ number_format((float) $transaction->quantity, 0, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-2">Preço por unidade</dt>
                        <dd class="mt-0.5 font-mono text-ink">R$ {{ number_format((float) $transaction->price, 4, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-2">Custos</dt>
                        <dd class="mt-0.5 font-mono text-ink">{{ $money->format((float) $transaction->fees) }}</dd>
                    </div>
                </dl>

                @if ($transaction->notes)
                    <div class="mt-6 pt-6 border-t border-line">
                        <p class="text-sm text-ink-2 mb-1">Observações</p>
                        <p class="text-ink whitespace-pre-line">{{ $transaction->notes }}</p>
                    </div>
                @endif
            </x-ui.card>

            <div class="space-y-6">
                <x-ui.card title="Ativo">
                    @if ($transaction->investment)
                        <p class="font-mono font-medium text-ink">{{ $transaction->investment->ticker }}</p>
                        <p class="text-sm text-ink-2">{{ $transaction->investment->name }}</p>
                        <p class="mt-2 text-sm text-ink-2">{{ $transaction->investment->investmentType?->name }}</p>
                        @if ($transaction->investment->trashed())
                            <p class="mt-2"><x-ui.badge tone="warning">Ativo excluído</x-ui.badge></p>
                        @else
                            <div class="mt-4">
                                <x-ui.button variant="secondary" size="sm" :href="route('investment.investments.show', $transaction->investment)">Ver ativo</x-ui.button>
                            </div>
                        @endif
                    @else
                        <p class="text-ink-2">—</p>
                    @endif
                </x-ui.card>

                <x-ui.card title="Registro">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-ink-2">Criada</dt><dd class="font-mono">{{ $transaction->created_at->format('d/m/Y H:i') }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-ink-2">Atualizada</dt><dd class="font-mono">{{ $transaction->updated_at->format('d/m/Y H:i') }}</dd></div>
                    </dl>
                    <div class="mt-6 pt-6 border-t border-line">
                        <x-ui.confirm-delete :action="route('investment.transactions.destroy', $transaction)" label="Excluir operação" size="md" />
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-finance-layout>
