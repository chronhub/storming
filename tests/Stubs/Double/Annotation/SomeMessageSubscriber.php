<?php

declare(strict_types=1);

namespace Storm\Tests\Stubs\Double\Annotation;

use Storm\Reporter\Attribute\AsSubscriber;

#[AsSubscriber(eventName: 'some_event', priority: 10)]
final class SomeMessageSubscriber
{
    public function __invoke(): callable
    {
        return fn (): int => 42;
    }
}
