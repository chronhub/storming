<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Message;

use Storm\Message\AliasFromClassName;

it('should return alias from class name', function () {
    $alias = new AliasFromClassName();
    expect($alias->toAlias(AliasFromClassName::class))->toBe(AliasFromClassName::class);
});

it('should return alias from object', function () {
    $alias = new AliasFromClassName();
    expect($alias->toAlias(new AliasFromClassName()))->toBe(AliasFromClassName::class);
});

it('raise exception when class does not exists', function () {
    $alias = new AliasFromClassName();
    /** @phpstan-ignore-next-line */
    $alias->toAlias('InvalidClass');
})->throws('Storm\Message\InvalidMessageAlias', 'Message class name InvalidClass does not exists.');
