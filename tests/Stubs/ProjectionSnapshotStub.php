<?php

declare(strict_types=1);

namespace Storm\Tests\Stubs;

use Storm\Projector\Checkpoint\CheckpointFactory;
use Storm\Projector\Storage\ProjectionSnapshot;

final class ProjectionSnapshotStub
{
    final public const string STREAM_NAME = 'stream1';

    final public const string CREATED_AT = '2024-01-01T00:00:00.000000';

    final public const array USER_STATE = ['foo' => 'bar', 'baz' => 'qux'];

    public function fromDefault(): ProjectionSnapshot
    {
        $checkpoint = CheckpointFactory::new(self::STREAM_NAME, self::CREATED_AT);

        return new ProjectionSnapshot([$checkpoint], self::USER_STATE);
    }
}
