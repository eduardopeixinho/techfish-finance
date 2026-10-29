<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-ink">
            Excluir conta
        </h2>

        <p class="mt-1 text-sm text-ink-2">
            Ao excluir a conta, todos os seus dados (contas, transações, categorias,
            investimentos e proventos) são apagados permanentemente. Exporte o que quiser
            guardar antes de continuar.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Excluir conta</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-ink">
                Tem certeza que quer excluir sua conta?
            </h2>

            <p class="mt-1 text-sm text-ink-2">
                Essa ação não pode ser desfeita. Para confirmar, digite o seu e-mail:
                <strong class="font-mono">{{ $user->email }}</strong>
            </p>

            <div class="mt-6">
                <x-input-label for="email_confirmation" value="Seu e-mail" class="sr-only" />

                <x-text-input
                    id="email_confirmation"
                    name="email_confirmation"
                    type="email"
                    autocomplete="off"
                    class="mt-1 block w-3/4"
                    placeholder="{{ $user->email }}"
                />

                <x-input-error :messages="$errors->userDeletion->get('email_confirmation')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    Excluir conta
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
