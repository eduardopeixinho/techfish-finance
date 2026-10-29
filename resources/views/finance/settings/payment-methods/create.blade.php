{{-- Tela genérica de catálogo: ver finance/settings/_crud e App\Support\SettingsCatalog. --}}
@include('finance.settings._crud.form', ['config' => \App\Support\SettingsCatalog::get('payment-methods'), 'item' => null])
