<?php

declare(strict_types=1);

namespace Storm\Tests;

use Illuminate\Contracts\Config\Repository;
use Orchestra\Testbench\TestCase;
use Storm\Support\Providers\StormServiceProvider;

class OrchestraTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        tap($app['config'], function (Repository $config) {
            $config->set('database.default', 'testbench');
            $config->set('database.connections.testbench', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
        });
    }

    protected function getPackageProviders($app): array
    {
        return [
            StormServiceProvider::class,
        ];
    }
}
