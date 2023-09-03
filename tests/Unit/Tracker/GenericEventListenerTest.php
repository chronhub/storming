<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Tracker;

use Storm\Contract\Tracker\Listener;
use Storm\Tracker\GenericListener;

it('can be instantiated', function () {
    $listener = new GenericListener(
        name: 'test',
        story: $story = fn () => null,
        priority: 10
    );

    expect($listener)->toBeInstanceOf(Listener::class)
        ->and($listener->name())->toBe('test')
        ->and($listener->priority())->toBe(10)
        ->and($listener->story())->toBe($story)
        ->and($listener->origin())->toBe('P\Tests\Unit\Tracker\GenericEventListenerTest');
});
