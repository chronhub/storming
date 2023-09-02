<?php

declare(strict_types=1);

namespace Storm\Tests\Stubs\Double\Annotation;

use Storm\Reporter\Attribute\AsSubscriber;

#[AsSubscriber(eventName: 'some_event', priority: 20, method: 'firstMethod')]
#[AsSubscriber(eventName: 'some_event', priority: 5, method: 'anotherMethod')]
final class SomeMessageSubscriberWithRepeatableAttribute
{
    public function firstMethod(): callable
    {
        return function (): string {
            return 'firstMethod';
        };
    }

    public function anotherMethod(): callable
    {
        return function (): string {
            return 'anotherMethod';
        };
    }
}
