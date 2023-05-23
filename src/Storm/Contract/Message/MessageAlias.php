<?php

declare(strict_types=1);

namespace Storm\Contract\Message;

interface MessageAlias
{
    /**
     * @param class-string $className
     */
    public function classToAlias(string $className): string;

    public function instanceToAlias(object $instance): string;
}
