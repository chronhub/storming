<?php

declare(strict_types=1);

namespace Storm\Chronicler\Tracker;

interface Listener
{
    /**
     * Get the name of the event.
     */
    public function name(): string;

    /**
     * Get the priority of the event.
     */
    public function priority(): int;

    /**
     * Get the callback function for the event.
     */
    public function callback(): callable;
}
