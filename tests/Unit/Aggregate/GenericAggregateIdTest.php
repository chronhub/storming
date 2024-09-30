<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Aggregate;

use Storm\Aggregate\Identity\GenericAggregateId;
use Storm\Contract\Aggregate\AggregateIdentity;
use Symfony\Component\Uid\Uuid;

it('assert generic aggregate id', function () {
    $id = Uuid::v4();
    $uid = $id->jsonSerialize();

    $aggregateId = GenericAggregateId::fromString($uid);

    expect($aggregateId)
        ->toBeInstanceOf(AggregateIdentity::class)
        ->and($aggregateId)->toBeInstanceOf(GenericAggregateId::class)
        ->and($aggregateId->id->equals($id))->toBeTrue()
        ->and($aggregateId->toString())->toBe($uid)
        ->and($aggregateId->equalsTo($aggregateId))->toBeTrue()
        ->and((string) $aggregateId)->toBe($uid)
        ->and($aggregateId->toString())->toBe($uid);

});
