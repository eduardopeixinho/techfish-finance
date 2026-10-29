<?php

/**
 * Modo demonstração: botão "Ver demonstração" que entra numa conta com dados fictícios
 * (criada pelo DemoSeeder). Pensado para quem clona o projeto sem credenciais do Google
 * e para uma instância pública de portfólio. Desligado por padrão.
 */
return [

    'enabled' => (bool) env('DEMO_MODE', false),

    'email' => env('DEMO_EMAIL', 'demo@techfish.test'),

    'name' => 'Conta Demonstração',

];
