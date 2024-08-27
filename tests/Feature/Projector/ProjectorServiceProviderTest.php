<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector;

use Storm\Contract\Projector\ProjectorManager;

test('projector manager', function () {
    expect($this->app['projector.manager'])->toBeInstanceOf(ProjectorManager::class);
});
