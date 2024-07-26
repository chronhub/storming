<?php

declare(strict_types=1);

namespace Storm\Contract\Tracker;

interface Listener
{
    /**
     * Return the name of the listener
     */
    public function name(): string;

    /**
     * Return the priority of the listener
     */
    public function priority(): int;

    /**
     * Return the story callable
     *
     * @return callable{Story}
     */
    public function story(): callable;

    /**
     * Return scope class name
     *
     * @return class-string
     */
    public function origin(): string;
}
