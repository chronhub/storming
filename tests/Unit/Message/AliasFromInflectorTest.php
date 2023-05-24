<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Message;

use Storm\Message\AliasFromInflector;

it('should return alias from class name', function () {
    $alias = new AliasFromInflector();
    expect($alias->toAlias(AliasFromInflector::class))->toBe('alias-from-inflector');
});

it('should return alias from object', function () {
    $alias = new AliasFromInflector();
    expect($alias->toAlias(new AliasFromInflector()))->toBe('alias-from-inflector');
});

it('raise exception when string class name does not exists', function () {
    $alias = new AliasFromInflector();
    /** @phpstan-ignore-next-line */
    $alias->toAlias('InvalidClass');
})->throws('Storm\Message\InvalidMessageAlias', 'Message class name InvalidClass does not exists.');
