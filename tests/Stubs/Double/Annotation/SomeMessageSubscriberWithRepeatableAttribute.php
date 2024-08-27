<?php

declare(strict_types=1);

namespace Storm\Tests\Stubs\Double\Annotation;

use Closure;
use Storm\Story\Attribute\AsSubscriber;

#[AsSubscriber(eventName: 'some_event', priority: 20, method: 'firstMethod')]
#[AsSubscriber(eventName: 'some_event', priority: 5, method: 'anotherMethod')]
final class SomeMessageSubscriberWithRepeatableAttribute
{
    public function firstMethod(): Closure
    {
        return function (): string {
            return 'firstMethod';
        };
    }

    public function anotherMethod(): Closure
    {
        return function (): string {
            return 'anotherMethod';
        };
    }
}
