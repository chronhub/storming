<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Support;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Storm\Support\ContainerAsClosure;

it('resolve container as closure', function (): void {
    $container = Container::getInstance();

    $asClosure = fn (): ContainerContract => $container;

    $instance = new ContainerAsClosure($asClosure);

    expect($instance)->toHaveProperty('container')
        ->and($instance->container)->toBe($container);
});
