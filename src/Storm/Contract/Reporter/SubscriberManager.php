<?php

declare(strict_types=1);

namespace Storm\Contract\Reporter;

interface SubscriberManager
{
    public function register(string $name): void;

    public function provides(): array;
}
