<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Subscription;

use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\Subscriptor;
use Storm\Projector\Subscription\HubManager;
use Storm\Projector\Workflow\Notification\Stream\StreamProcessed;

beforeEach(function () {
    $this->subscriptor = mock(Subscriptor::class);
    $this->hub = new HubManager($this->subscriptor);
});

dataset('stream process handler', [
    'as string' => fn () => StreamProcessed::class,
    'as callable string' => fn () => fn () => StreamProcessed::class,
    'as callable' => fn () => fn () => new StreamProcessed('foo'),
    'as array' => fn () => [StreamProcessed::class],
]);

test('default instance', function () {
    expect($this->hub)->toBeInstanceOf(NotificationHub::class)
        ->and($this->hub->hasListener('foo'))->toBeFalse();
});

test('add listener with handler', function (string|callable|array $handler) {
    expect($this->hub->hasListener('foo'))->toBeFalse();
    $this->hub->addListener('foo', $handler);
    expect($this->hub->hasListener('foo'))->toBeTrue();
})->with('stream process handler');
