<?php

declare(strict_types=1);

namespace Storm\Tests;

use Orchestra\Testbench\TestCase;
use Storm\Support\Providers\StormServiceProvider;

class OrchestraTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [StormServiceProvider::class];
    }
}
