<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Message;

use Storm\Contract\Message\DomainType;
use Storm\Message\AbstractDomainCommand;
use Storm\Tests\Stubs\Double\Message\SomeCommand;

dataset('domainCommand', [
    'construct' => fn (): AbstractDomainCommand => new class(['foo' => 'bar']) extends AbstractDomainCommand
    {
    },
    'static' => fn (): SomeCommand => SomeCommand::fromContent(['foo' => 'bar']),
]);

it('create new instance', function (object $command) {
    expect($command->toContent())->toBe(['foo' => 'bar'])
        ->and($command->supportType())->toBe(DomainType::COMMAND)
        ->and($command->headers())->toBeEmpty();
})->with('domainCommand');
