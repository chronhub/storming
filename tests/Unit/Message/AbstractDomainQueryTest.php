<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Message;

use Storm\Contract\Message\DomainType;
use Storm\Message\AbstractDomainQuery;
use Storm\Tests\Stubs\Double\Message\SomeQuery;

it('can be constructed', function () {
    $query = new class(['foo' => 'bar']) extends AbstractDomainQuery
    {
    };

    expect($query->toContent())->toBe(['foo' => 'bar'])
        ->and($query->supportType())->toBe(DomainType::QUERY)
        ->and($query->headers())->toBeEmpty();
});

it('can be instantiated', function () {
    $query = SomeQuery::fromContent(['foo' => 'bar']);

    expect($query->toContent())->toBe(['foo' => 'bar'])
        ->and($query->supportType())->toBe(DomainType::QUERY)
        ->and($query->headers())->toBeEmpty();
});
