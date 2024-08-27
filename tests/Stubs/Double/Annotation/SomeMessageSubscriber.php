<?php

declare(strict_types=1);

namespace Storm\Tests\Stubs\Double\Annotation;

use Closure;
use Storm\Story\Attribute\AsSubscriber;

#[AsSubscriber(eventName: 'some_event', priority: 10)]
final class SomeMessageSubscriber
{
    public function __invoke(): Closure
    {
        return fn (): int => 42;
    }
}
