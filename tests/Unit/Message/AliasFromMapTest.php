<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Message;

use Storm\Message\AliasFromClassName;
use Storm\Message\AliasFromMap;

dataset('map', [
    [
        ['single_map',  AliasFromClassName::class => 'alias-from-map'],
    ],
]);

it('should return alias with string class name from map', function (array $map) {

    $alias = new AliasFromMap($map);
    expect($alias->toAlias(AliasFromClassName::class))->toBe('alias-from-map');
})->with('map');

it('should return alias with object from map', function (array $map) {
    $alias = new AliasFromMap($map);
    expect($alias->toAlias(new AliasFromClassName()))->toBe('alias-from-map');
})->with('map');

it('raise exception when class does not exists', function () {
    $alias = new AliasFromMap([]);
    /** @phpstan-ignore-next-line */
    $alias->toAlias('InvalidClass');
})->throws('Storm\Message\InvalidMessageAlias', 'Message class name InvalidClass does not exists.');

it('raise exception when alias does not exists in map', function () {
    $alias = new AliasFromMap([]);
    $alias->toAlias(AliasFromClassName::class);
})->throws('Storm\Message\InvalidMessageAlias', 'Message class name Storm\Message\AliasFromClassName not found in map.');
