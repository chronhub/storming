<?php

declare(strict_types=1);

namespace Storm\Contract\Tracker;

use Storm\Contract\Message\MessageDecorator;

interface StreamStory extends Story
{
    public function deferred(callable $callback): void;

    public function promise(): mixed;

    public function decorate(MessageDecorator $messageDecorator): void;

    public function hasStreamNotFound(): bool;

    public function hasStreamAlreadyExits(): bool;

    public function hasConcurrency(): bool;
}
