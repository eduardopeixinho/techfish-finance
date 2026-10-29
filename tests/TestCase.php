<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Desabilitar CSRF em testes (Laravel 13+ deveria fazer isso automaticamente)
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    /**
     * Trava de segurança: roda antes dos traits (RefreshDatabase etc.).
     *
     * Se a suíte não estiver no SQLite em memória, aborta ANTES de migrar — senão o
     * RefreshDatabase apagaria o banco configurado (já aconteceu dentro do Docker, cujo
     * ambiente exporta DB_CONNECTION=mysql; ver os <server force="true"> no phpunit.xml).
     *
     * @return array<class-string, class-string>
     */
    protected function setUpTraits()
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if (! app()->environment('testing') || $connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(sprintf(
                'Testes abortados para proteger o banco: ambiente "%s", conexão "%s", banco "%s". '
                .'Esperado: testing / sqlite / :memory: (confira o phpunit.xml).',
                app()->environment(), $connection, $database
            ));
        }

        return parent::setUpTraits();
    }
}
