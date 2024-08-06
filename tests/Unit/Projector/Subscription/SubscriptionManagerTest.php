<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Subscription;

use BadMethodCallException;
use stdClass;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Provider\AgentManager;
use Storm\Projector\Workflow\Component;
use TypeError;

beforeEach(function () {
    $this->eventStreamProvider = mock(EventStreamProvider::class);
    $this->watchers = mock(Component::class);
    $this->subscriptionManager = new AgentManager($this->watchers);
});

test('default instance', function () {
    expect($this->subscriptionManager)->toBeInstanceOf(AgentManager::class)
        ->and($this->subscriptionManager->getContext())->toBeNull()
        ->and($this->subscriptionManager->currentStatus())->toBe(ProjectionStatus::IDLE);
});

test('set context', function () {
    $context = mock(ContextReader::class);

    expect($this->subscriptionManager->getContext())->toBeNull();

    $this->subscriptionManager->setContext($context);

    expect($this->subscriptionManager->getContext())->toBe($context);
});

test('set projection status', function (ProjectionStatus $status) {
    expect($this->subscriptionManager->currentStatus())->toBe(ProjectionStatus::IDLE);

    $this->subscriptionManager->setStatus($status);

    expect($this->subscriptionManager->currentStatus())->toBe($status);
})->with('projection status');

test('raise type error exception with get process stream when stream processed not set', function () {
    try {
        $currentStream = $this->subscriptionManager->getProcessedStream();
    } catch (TypeError $e) {
        expect($e->getMessage())->toContain('Return value must be of type string, null returned');
    }
});

test('set processed stream', function (string $streamName) {
    $this->subscriptionManager->setProcessedStream($streamName);

    expect($this->subscriptionManager->getProcessedStream())->toBe($streamName);
})->with([['stream1'], ['stream-2']]);

test('subscribe to watcher manager', function () {
    $hub = mock(NotificationHub::class);
    $context = mock(ContextReader::class);

    $this->watchers->expects('subscribe')->with($hub, $context);

    $this->subscriptionManager->subscribe($hub, $context);
});

test('capture event with callable and return result', function () {
    $callable = fn (AgentManager $subscription) => 'foo';

    expect($this->subscriptionManager->capture($callable))->toBe('foo');
});

test('capture event with non callable object and return same object', function () {
    $object = new stdClass();
    expect($this->subscriptionManager->capture($object))->toBe($object);
});

test('dynamic call to watcher manager', function () {
    $this->watchers->expects('foo')->andReturns(new stdClass());

    $result = $this->subscriptionManager->__call('foo', []);
    expect($result)->toBeInstanceOf(stdClass::class);
});

test('raise exception from watcher manager when dynamic method call is unknown', function () {
    $exception = new BadMethodCallException('Watcher bar not found');
    $this->subscriptionManager->__call('bar', [])->throws($exception);
})->throws(BadMethodCallException::class);
