<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Message;

use Storm\Contract\Message\DomainType;
use Storm\Message\DomainEvent;
use Storm\Tests\Stubs\Double\Message\SomeEvent;

dataset('domainEvent', [
    'construct' => fn (): DomainEvent => new class(['foo' => 'bar']) extends DomainEvent {},
    'static' => fn (): SomeEvent => SomeEvent::fromContent(['foo' => 'bar']),
]);

it('create new instance', function (object $event) {
    expect($event->toContent())->toBe(['foo' => 'bar'])
        ->and($event->supportType())->toBe(DomainType::EVENT)
        ->and($event->headers())->toBeEmpty();
})->with('domainEvent');
