<?php

declare(strict_types=1);

namespace Storm\Tests;

use Orchestra\Testbench\TestCase;
use Storm\Reporter\ReporterServiceProvider;

class OrchestraTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ReporterServiceProvider::class,
        ];
    }
}
