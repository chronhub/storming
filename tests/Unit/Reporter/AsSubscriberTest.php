<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Reporter;

use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\Tracker;
use Storm\Reporter\Attribute\AsSubscriber;

it('assert instance', function (string $eventName, int $priority, ?string $method) {
    $attribute = new AsSubscriber($eventName, $priority, $method);

    expect($attribute->eventName)->toBe($eventName)
        ->and($attribute->priority)->toBe($priority)
        ->and($attribute->method)->toBe($method);

})
    ->with([Reporter::DISPATCH_EVENT, Reporter::FINALIZE_EVENT, 'foo'])
    ->with([Tracker::DEFAULT_PRIORITY, 100, -100])
    ->with([null, 'some_method', 'another_method']);
