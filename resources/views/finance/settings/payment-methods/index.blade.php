{{-- Tela genérica de catálogo: ver finance/settings/_crud e App\Support\SettingsCatalog. --}}
@include('finance.settings._crud.index', ['config' => \App\Support\SettingsCatalog::get('payment-methods'), 'items' => $paymentMethods])
