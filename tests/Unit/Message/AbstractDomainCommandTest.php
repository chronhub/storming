<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Message;

use Storm\Contract\Message\DomainType;
use Storm\Message\AbstractDomainCommand;
use Storm\Tests\Stubs\Double\Message\SomeCommand;

it('can be constructed', function () {
    $command = new class(['foo' => 'bar']) extends AbstractDomainCommand
    {
    };

    expect($command->toContent())->toBe(['foo' => 'bar'])
        ->and($command->supportType())->toBe(DomainType::COMMAND)
        ->and($command)->toHaveProperty('headers')
        ->and($command)->toHaveProperty('content')
        ->and($command->headers())->toBeEmpty()
        ->and($command->toContent())->toBe(['foo' => 'bar']);
});

it('can be instantiated', function () {
    $command = SomeCommand::fromContent(['foo' => 'bar']);

    expect($command->toContent())->toBe(['foo' => 'bar'])
        ->and($command->supportType())->toBe(DomainType::COMMAND)
        ->and($command->headers())->toBeEmpty();
});
