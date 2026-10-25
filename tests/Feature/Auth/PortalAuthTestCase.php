<?php

namespace Tests\Feature\Auth;

use Illuminate\Contracts\Console\Kernel;
use Tests\TestCase;

abstract class PortalAuthTestCase extends TestCase
{
    public function createApplication()
    {
        $ambiente = [
            'APP_ENV' => 'testing',
            'APP_KEY' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
            'APP_CONFIG_CACHE' => 'bootstrap/cache/portal84-config-inexistente.php',
            'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:',
            'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync',
            'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array',
            'BCRYPT_ROUNDS' => '4', 'TELEGRAM_ENABLED' => 'false',
        ];
        foreach ($ambiente as $nome => $valor) {
            putenv($nome.'='.$valor);
            $_ENV[$nome] = $_SERVER[$nome] = $valor;
        }
        $app = require dirname(__DIR__, 3).'/bootstrap/app.php';
        $app->loadEnvironmentFrom('.env.portal84-testing-inexistente');
        $this->traitsUsedByTest = array_flip(class_uses_recursive(static::class));
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
