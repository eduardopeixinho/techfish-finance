<x-app-layout>
<div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-finance-paper border border-line sm:rounded-sm">
                <div class="max-w-xl">
                    <h3 class="text-lg font-medium text-ink">
                        {{ __('Informações do Usuário') }}
                    </h3>
                    <p class="mt-2 text-sm text-ink-2">
                        <strong>{{ __('Name:') }}</strong> {{ Auth::user()->name }}
                    </p>
                    <p class="text-sm text-ink-2">
                        <strong>{{ __('Email:') }}</strong> {{ Auth::user()->email }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
