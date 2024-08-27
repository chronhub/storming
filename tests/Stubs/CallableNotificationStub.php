<?php

declare(strict_types=1);

namespace Storm\Tests\Stubs;

use Storm\Contract\Projector\NotificationHub;

final class CallableNotificationStub
{
    public function __construct(public int $value = 42) {}

    public function __invoke(NotificationHub $hub, object $notification, int $value): int
    {
        return $this->value;
    }
}
