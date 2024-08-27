<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Message;

use Storm\Message\AliasFromInflector;
use Storm\Message\InvalidMessageAlias;

it('return alias from class name', function () {
    $alias = new AliasFromInflector();

    expect($alias->toAlias(AliasFromInflector::class))->toBe('alias-from-inflector');
});

it('return alias from object', function () {
    $alias = new AliasFromInflector();

    expect($alias->toAlias(new AliasFromInflector()))->toBe('alias-from-inflector');
});

it('raises exception when string class name does not exists', function () {
    $alias = new AliasFromInflector();

    /** @phpstan-ignore-next-line */
    $alias->toAlias('InvalidClass');
})->throws(InvalidMessageAlias::class, 'Message class name InvalidClass does not exists.');
