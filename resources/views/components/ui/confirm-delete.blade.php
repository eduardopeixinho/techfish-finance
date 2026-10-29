{{--
    Exclusão com confirmação em dois cliques (substitui o confirm() nativo do navegador).
    1º clique: o botão vira o texto de confirmação; 2º clique envia. Sai do modo de
    confirmação ao perder o foco ou após 4 s.

    Props:
      $action:       URL do DELETE
      $label:        texto do botão (padrão "Excluir")
      $confirmLabel: texto no modo de confirmação (padrão "Confirmar exclusão")
      $size:         'md' | 'sm'
      $method:       verbo HTTP do form (padrão DELETE; ex.: PATCH para desativar)
    Slot (opcional): campos extras do form, ex. <input type="hidden" name="role_id" ...>
--}}
@props(['action', 'label' => 'Excluir', 'confirmLabel' => 'Confirmar exclusão', 'size' => 'sm', 'method' => 'DELETE'])

<form method="POST" action="{{ $action }}" class="inline"
      x-data="{ confirming: false, timer: null }"
      @submit="if (! confirming) { $event.preventDefault(); confirming = true; clearTimeout(timer); timer = setTimeout(() => confirming = false, 4000) }">
    @csrf
    @method($method)
    {{ $slot }}
    <x-ui.button type="submit" variant="danger" :size="$size"
                 @blur="confirming = false"
                 ::class="confirming && '!bg-red-700 !text-white !border-red-700'"
                 {{ $attributes }}>
        <span x-text="confirming ? @js($confirmLabel) : @js($label)">{{ $label }}</span>
    </x-ui.button>
</form>
