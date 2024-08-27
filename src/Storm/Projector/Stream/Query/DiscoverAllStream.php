<?php

declare(strict_types=1);

namespace Storm\Projector\Stream\Query;

use Storm\Contract\Chronicler\EventStreamProvider;

final class DiscoverAllStream
{
    /**
     * @return array|array<string>
     */
    public function __invoke(EventStreamProvider $provider): array
    {
        return $provider->all();
    }
}
