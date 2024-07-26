<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Message;

use Storm\Message\AliasFromClassName;
use Storm\Message\InvalidMessageAlias;

it('return alias from class name', function () {
    $alias = new AliasFromClassName();

    expect($alias->toAlias(AliasFromClassName::class))->toBe(AliasFromClassName::class);
});

it('return alias from object', function () {
    $alias = new AliasFromClassName();

    expect($alias->toAlias(new AliasFromClassName()))->toBe(AliasFromClassName::class);
});

it('raises exception when class does not exists', function () {
    $alias = new AliasFromClassName();

    /** @phpstan-ignore-next-line */
    $alias->toAlias('InvalidClass');
})->throws(InvalidMessageAlias::class, 'Message class name InvalidClass does not exists.');
