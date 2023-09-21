<?php

declare(strict_types=1);

namespace Storm\Tests\Feature;

use Storm\Contract\Reporter\Reporter;
use Storm\Reporter\ReportCommand;
use Storm\Reporter\Subscriber\DispatchMessage;
use Storm\Reporter\Subscriber\HandleCommand;
use Storm\Reporter\Subscriber\MakeMessage;
use Storm\Reporter\Subscriber\RequireOneMessageHandlerOnly;
use Storm\Reporter\Subscriber\RouteMessage;
use Storm\Tests\Stubs\Double\Message\SomeCommand;
use Storm\Tests\Stubs\Double\Message\SomeEvent;

it('test dispatch', function () {
    expect($this->app->bound(ReportCommand::class))->toBeTrue()
        ->and($this->app->bound('reporter-command-default'))->toBeTrue();

    /** @var Reporter $reporter */
    $reporter = $this->app['reporter-command-default'];
    expect($reporter)->toBe($this->app[ReportCommand::class]);

    $reporter->subscribe(
        MakeMessage::class,
        RouteMessage::class,
        DispatchMessage::class,
        HandleCommand::class,
        RequireOneMessageHandlerOnly::class
    );

    $message = SomeCommand::fromContent(['foo' => 'bar']);
    //$message = SomeEvent::fromContent(['foo' => 'bar']);

    $reporter->relay($message);
});
