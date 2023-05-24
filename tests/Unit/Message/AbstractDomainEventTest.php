<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Message;

use Storm\Contract\Message\DomainType;
use Storm\Message\AbstractDomainEvent;
use Storm\Tests\Stubs\Double\Message\SomeEvent;

it('can be constructed', function () {
    $event = new class(['foo' => 'bar']) extends AbstractDomainEvent
    {
    };

    expect($event->toContent())->toBe(['foo' => 'bar'])
        ->and($event->supportType())->toBe(DomainType::EVENT)
        ->and($event->headers())->toBeEmpty();
});

it('can be instantiated', function () {
    $event = SomeEvent::fromContent(['foo' => 'bar']);

    expect($event->toContent())->toBe(['foo' => 'bar'])
        ->and($event->supportType())->toBe(DomainType::EVENT)
        ->and($event->headers())->toBeEmpty();
});
