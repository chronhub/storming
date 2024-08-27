<?php

declare(strict_types=1);

namespace Storm\Tests\Testing;

use Storm\Contract\Projector\ProjectionProvider;
use Storm\Projector\Repository\Data\CreateData;

/**
 * @property ProjectionProvider $projectionProvider
 */
trait ProjectionProviderTestingTrait
{
    public function createProjection(string $name, string $status): void
    {
        expect($this->projectionProvider->exists($name))->toBeFalse();

        $data = new CreateData($status);
        $this->projectionProvider->createProjection($name, $data);

        expect($this->projectionProvider->exists($name))->toBeTrue();
    }
}
