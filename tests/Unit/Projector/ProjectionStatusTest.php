<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector;

use Storm\Projector\ProjectionStatus;

test('get array of projection status strings', function () {
    expect(ProjectionStatus::strings())->toBe(
        [
            'running',
            'stopping',
            'deleting',
            'deleting_with_emitted_events',
            'resetting',
            'idle',
        ]
    );
});
