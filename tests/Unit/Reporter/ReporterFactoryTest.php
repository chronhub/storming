<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Reporter;

use Illuminate\Container\Container;
use Storm\Reporter\ReporterFactory;
use Storm\Tests\Unit\Reporter\Stub\ReportCommandStub;

it('test instance', function () {
    $container = Container::getInstance();

    $factory = new ReporterFactory($container);

    $reporter = $factory->create(ReportCommandStub::class);

    expect($reporter)->toBeInstanceOf(ReportCommandStub::class);
});
