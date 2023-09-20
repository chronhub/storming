<?php

declare(strict_types=1);

namespace Storm\Tests\Feature;

use Storm\Contract\Reporter\Reporter;
use Storm\Reporter\Subscriber\DispatchMessage;
use Storm\Reporter\Subscriber\HandleCommand;
use Storm\Reporter\Subscriber\MakeMessage;
use Storm\Reporter\Subscriber\RouteMessage;
use Storm\Tests\Stubs\Double\Message\SomeCommand;

it('test dispatch', function () {
    /** @var Reporter $reporter */
    $reporter = $this->app['reporter-command-default'];

    $reporter->subscribe(
        MakeMessage::class,
        RouteMessage::class,
        DispatchMessage::class,
        HandleCommand::class,
    );

    $message = SomeCommand::fromContent(['foo' => 'bar']);

    $reporter->relay($message);
});
