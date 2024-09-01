<?php

declare(strict_types=1);

namespace Storm\Aggregate\Connector;

interface Connector
{
    public function connect(array $config): ConnectionManager;
}
