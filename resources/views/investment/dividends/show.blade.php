{{-- Detalhe do provento. Valores em R$ via $money; valor por cota (6 casas) é cotação e fica visível. --}}
@php
    use App\Support\Format;

    $gross = (float) $dividend->amount;
    $taxRate = $gross > 0 ? ((float) $dividend->tax_withheld / $gross) * 100 : 0;
@endphp

<x-finance-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header :title="$dividend->type_name.' de '.($dividend->investment?->ticker ?? 'ativo')" :kicker="'Proventos · pago em '.$dividend->payment_date->format('d/m/Y')">
            <x-slot:actions>
                <x-ui.button variant="accent" :href="route('investment.dividends.edit', $dividend)">Editar</x-ui.button>
                <x-ui.button variant="secondary" :href="route('investment.dividends.index')">Proventos</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.flash />

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <x-ui.card class="lg:col-span-2">
                <p class="text-sm text-ink-2">Valor líquido recebido</p>
                <p class="mt-1 font-serif text-4xl text-emerald-700">{{ $money->format((float) $dividend->net_amount) }}</p>

                <dl class="mt-6 pt-6 border-t border-line grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-ink-2">Por cota</dt>
                        <dd class="mt-0.5 font-mono text-ink">R$ {{ number_format((float) $dividend->amount_per_share, 6, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-2">Cotas</dt>
                        <dd class="mt-0.5 font-mono text-ink">{{ number_format((float) $dividend->quantity_owned, 0, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-2">Bruto</dt>
                        <dd class="mt-0.5 font-mono text-ink">{{ $money->format($gross) }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-2">IR retido</dt>
                        <dd class="mt-0.5 font-mono text-red-700">
                            {{ $money->format((float) $dividend->tax_withheld) }}
                            @if ($taxRate > 0) <span class="text-ink-2">({{ Format::percent($taxRate, 2) }})</span> @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-ink-2">Data com</dt>
                        <dd class="mt-0.5 font-mono text-ink">{{ $dividend->record_date->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-2">Pagamento</dt>
                        <dd class="mt-0.5 font-mono text-ink">
                            {{ $dividend->payment_date->format('d/m/Y') }}
                            <span class="block font-sans text-xs text-ink-2">{{ $dividend->payment_date->isFuture() ? 'previsto' : 'pago' }} {{ $dividend->payment_date->diffForHumans() }}</span>
                        </dd>
                    </div>
                </dl>

                @if ($dividend->notes)
                    <div class="mt-6 pt-6 border-t border-line">
                        <p class="text-sm text-ink-2 mb-1">Observações</p>
                        <p class="text-ink whitespace-pre-line">{{ $dividend->notes }}</p>
                    </div>
                @endif
            </x-ui.card>

            <div class="space-y-6">
                <x-ui.card title="Ativo">
                    @if ($dividend->investment)
                        <p class="font-mono font-medium text-ink">{{ $dividend->investment->ticker }}</p>
                        <p class="text-sm text-ink-2">{{ $dividend->investment->name }}</p>
                        <p class="mt-2 text-sm text-ink-2">{{ $dividend->investment->investmentType?->name }}</p>
                        @if ($dividend->investment->trashed())
                            <p class="mt-2"><x-ui.badge tone="warning">Ativo excluído</x-ui.badge></p>
                        @else
                            <div class="mt-4 flex flex-wrap gap-2">
                                <x-ui.button variant="secondary" size="sm" :href="route('investment.investments.show', $dividend->investment)">Ver ativo</x-ui.button>
                                <x-ui.button variant="secondary" size="sm" :href="route('investment.dividends.create', ['investment_id' => $dividend->investment->id])">Novo provento</x-ui.button>
                            </div>
                        @endif
                    @endif
                </x-ui.card>

                <x-ui.card title="Registro">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-ink-2">Criado</dt><dd class="font-mono">{{ $dividend->created_at->format('d/m/Y H:i') }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-ink-2">Atualizado</dt><dd class="font-mono">{{ $dividend->updated_at->format('d/m/Y H:i') }}</dd></div>
                    </dl>
                    <div class="mt-6 pt-6 border-t border-line">
                        <x-ui.confirm-delete :action="route('investment.dividends.destroy', $dividend)" label="Excluir provento" size="md" />
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-finance-layout>
