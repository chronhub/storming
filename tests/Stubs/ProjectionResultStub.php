<?php

declare(strict_types=1);

namespace Storm\Tests\Stubs;

use Storm\Projector\Checkpoint\CheckpointFactory;
use Storm\Projector\Repository\ProjectionResult;

final class ProjectionResultStub
{
    final public const string STREAM_NAME = 'some_stream_name';

    final public const string CREATED_AT = '2024-01-01 00:00:00';

    final public const array USER_STATE = ['foo' => 'bar', 'baz' => 'qux'];

    public function fromDefault(): ProjectionResult
    {
        $checkpoint = CheckpointFactory::fromEmpty(self::STREAM_NAME, self::CREATED_AT);

        return new ProjectionResult([self::STREAM_NAME => $checkpoint], self::USER_STATE);
    }
}
