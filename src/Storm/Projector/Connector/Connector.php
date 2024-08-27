<?php

declare(strict_types=1);

namespace Storm\Projector\Connector;

interface Connector
{
    /**
     * Connect to the underlying storage.
     */
    public function connect(array $config): ConnectionManager;
}
