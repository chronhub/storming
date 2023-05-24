<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Tracker;

use Storm\Contract\Tracker\EventListener;
use Storm\Tracker\GenericEventListener;

it('can be instantiated', function () {
    $listener = new GenericEventListener(
        name: 'test',
        story: $story = fn () => null,
        priority: 10
    );

    expect($listener)->toBeInstanceOf(EventListener::class)
        ->and($listener->name())->toBe('test')
        ->and($listener->priority())->toBe(10)
        ->and($listener->story())->toBe($story)
        ->and($listener->origin())->toBe('P\Tests\Unit\Tracker\GenericEventListenerTest');
});
