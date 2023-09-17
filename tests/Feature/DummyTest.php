<?php

declare(strict_types=1);

namespace Storm\Tests\Feature;

use ReflectionClass;
use Storm\Attribute\Loader;
use Storm\Reporter\ReportCommand;
use Storm\Reporter\Subscriber\DispatchMessage;
use Storm\Tests\Unit\Reporter\Stub\ReportCommandStub;

it('test', function () {
    /** @var Loader $loader */
    $loader = $this->app[Loader::class];

    dump($loader->getMap()->jsonSerialize());
});

it('find reporter name', function () {

    /** @var Loader $loader */
    $loader = $this->app[Loader::class];

    dump($loader->getReporter('foo'));
    dump($loader->getReporter(ReportCommandStub::class));
    dump($loader->getReporter(ReportCommand::class));
    dump($loader->getReporter(ReportCommand::class));
    dump($loader->getReporter('reporter-command-default'));
});

it('du', function () {

    $class = new ReflectionClass(DispatchMessage::class);

    $methods = $class->getMethods();

    dump($methods);
});

it('files', function () {

});
