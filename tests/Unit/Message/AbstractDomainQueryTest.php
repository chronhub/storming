<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Message;

use Storm\Contract\Message\DomainType;
use Storm\Message\DomainQuery;
use Storm\Tests\Stubs\Double\Message\SomeQuery;

dataset('domainQuery', [
    'construct' => fn (): DomainQuery => new class(['foo' => 'bar']) extends DomainQuery {},
    'static' => fn (): SomeQuery => SomeQuery::fromContent(['foo' => 'bar']),
]);

it('create new instance', function (object $query) {
    expect($query->toContent())->toBe(['foo' => 'bar'])
        ->and($query->supportType())->toBe(DomainType::QUERY)
        ->and($query->headers())->toBeEmpty();
})->with('domainQuery');
