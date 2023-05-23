<?php

declare(strict_types=1);

namespace Storm\Contract\Tracker;

interface EventListener
{
    public function name(): string;

    public function priority(): int;

    /**
     * @return callable(Story)
     */
    public function story(): callable;

    /**
     * Return scope class name
     *
     * @return class-string|null
     */
    public function origin(): ?string;
}
