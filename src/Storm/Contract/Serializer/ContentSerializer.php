<?php

declare(strict_types=1);

namespace Storm\Contract\Serializer;

use Storm\Contract\Message\Messaging;
use Storm\Serializer\Payload;

interface ContentSerializer
{
    /**
     * Serialize Message event
     */
    public function serialize(Messaging $messaging): array;

    /**
     * Deserialize Message event
     */
    public function deserialize(string $source, Payload $payload): object;
}
