<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Message;

use Storm\Message\AbstractDomainCommand;
use Storm\Message\DomainType;
use Storm\Tests\Stubs\Double\Message\SomeCommand;

dataset('domain command', [
    'construct' => fn (): AbstractDomainCommand => new class(['foo' => 'bar']) extends AbstractDomainCommand {},
    'static' => fn (): SomeCommand => SomeCommand::fromContent(['foo' => 'bar']),
]);

it('create new instance', function (object $command) {
    expect($command->toContent())->toBe(['foo' => 'bar'])
        ->and($command->type())->toBe(DomainType::COMMAND)
        ->and($command->headers())->toBeEmpty();
})->with('domain command');
