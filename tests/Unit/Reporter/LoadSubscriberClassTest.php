<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Reporter;

use RuntimeException;
use stdClass;
use Storm\Reporter\Loader\LoadSubscriberClass;
use Storm\Tests\Stubs\Double\Annotation\SomeMessageSubscriber;
use Storm\Tests\Stubs\Double\Annotation\SomeMessageSubscriberWithRepeatableAttribute;
use Storm\Tracker\GenericListener;

use function is_string;

it('load AsSubscriber attribute', function (string|object $subscriber): void {
    $eventListeners = LoadSubscriberClass::from($subscriber);

    expect($eventListeners)->toHaveCount(1);

    $eventListener = $eventListeners[0];

    $instance = is_string($subscriber) ? new $subscriber : $subscriber;

    expect($eventListener)->toBeInstanceOf(GenericListener::class)
        ->and($eventListener->name())->toBe('some_event')
        ->and($eventListener->priority())->toBe(10)
        ->and($eventListener->story())->toEqual($instance())->not()->toBe($instance)
        ->and($instance()())->toBe(42);
})->with([
    'class' => SomeMessageSubscriber::class,
    'object' => new SomeMessageSubscriber(),
]);

it('load AsSubscriber repeatable attribute', function (string|object $subscriber): void {
    $eventListeners = LoadSubscriberClass::from($subscriber);

    expect($eventListeners)->toHaveCount(2);

    $firstListener = $eventListeners[0];

    expect($firstListener)->toBeInstanceOf(GenericListener::class)
        ->and($firstListener->name())->toBe('some_event')
        ->and($firstListener->priority())->toBe(20)
        ->and($firstListener->story()())->toBe('firstMethod');

    $anotherListener = $eventListeners[1];

    expect($anotherListener)->toBeInstanceOf(GenericListener::class)
        ->and($anotherListener->name())->toBe('some_event')
        ->and($anotherListener->priority())->toBe(5)
        ->and($anotherListener->story()())->toBe('anotherMethod');

})->with([
    'class' => SomeMessageSubscriberWithRepeatableAttribute::class,
    'object' => new SomeMessageSubscriberWithRepeatableAttribute(),
]);

it('raise exception when attribute is missing', function (string|object $subscriber): void {
    LoadSubscriberClass::from($subscriber);
})->with([
    'class' => stdClass::class,
    'object' => new stdClass(),
])->throws(RuntimeException::class, 'Missing attribute #AsSubscriber for class '.stdClass::class);
