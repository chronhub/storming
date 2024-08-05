<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector;

use Storm\Contract\Projector\ProjectorManagerInterface;

test('projector manager', function () {
    expect($this->app['projector.manager'])->toBeInstanceOf(ProjectorManagerInterface::class);
});
