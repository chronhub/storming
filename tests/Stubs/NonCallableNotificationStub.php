<?php

declare(strict_types=1);

namespace Storm\Tests\Stubs;

final class NonCallableNotificationStub
{
    public function __construct(
        public string $value,
        public int $priority
    ) {}
}
