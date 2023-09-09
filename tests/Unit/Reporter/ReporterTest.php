<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Reporter;

use InvalidArgumentException;
use Mockery;
use React\Promise\PromiseInterface;
use RuntimeException;
use stdClass;
use Storm\Contract\Message\Header;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\MessageStory;
use Storm\Contract\Tracker\MessageTracker;
use Storm\Message\Message;
use Storm\Reporter\MessageNotHandled;
use Storm\Tests\Stubs\Double\Message\SomeCommand;
use Storm\Tests\Stubs\Double\Message\SomeEvent;
use Storm\Tests\Stubs\Double\Message\SomeQuery;
use Storm\Tests\Unit\Reporter\Stub\ReportCommandStub;
use Storm\Tests\Unit\Reporter\Stub\ReportEventStub;
use Storm\Tests\Unit\Reporter\Stub\ReportQueryStub;
use Storm\Tracker\GenericListener;
use Storm\Tracker\TrackMessage;
use Throwable;

beforeEach(function () {
    $this->tracker = mock(MessageTracker::class);
});

afterEach(function () {
    $this->tracker = null;
});

dataset('reporter', [
    'command' => ReportCommandStub::class,
    'event' => ReportEventStub::class,
    'query' => ReportQueryStub::class,
]);

dataset('message', [
    'command' => SomeCommand::fromContent(['name' => 'step bug']),
    'event' => SomeEvent::fromContent(['name' => 'step bug']),
    'query' => SomeQuery::fromContent(['name' => 'step bug']),
    'object' => new stdClass(),
]);

it('test instance', function (string $reporterClass) {
    $reporter = new $reporterClass($this->tracker);

    expect($reporter->tracker)->toBe($this->tracker);
})->with('reporter');

it('add subscriber', function (string $reporterClass) {
    $tracker = new TrackMessage();
    $reporter = new $reporterClass($tracker);

    expect($reporter->tracker)->toBe($tracker)
        ->and($tracker->listeners())->toHaveCount(0);

    $listener = new GenericListener('foo', fn () => 42, 12);
    $reporter->subscribe($listener);

    expect($reporter->tracker->listeners())->toHaveCount(1);

    /** @var GenericListener $subscriber */
    $subscriber = $tracker->listeners()->first();

    expect($subscriber->name())->toBe('foo')
        ->and($subscriber->priority())->toBe(12)
        ->and($subscriber->story())->toEqual(fn () => 42);
})->with('reporter');

it('report', function (string $reporterClass, object $object) {
    $reporter = new $reporterClass($this->tracker);
    $story = mock(MessageStory::class);

    $this->tracker->shouldReceive('newStory')->once()->with(Reporter::DISPATCH_EVENT)->andReturn($story);

    $story->shouldReceive('withTransientMessage')->once()->with($object);

    // dispatch
    $this->tracker->shouldReceive('disclose')->once()->with($story);
    $story->shouldReceive('isHandled')->once()->andReturn(true);
    $story->shouldReceive('stop')->once()->with(false);

    // finalize
    $story->shouldReceive('withEvent')->once()->with(Reporter::FINALIZE_EVENT);
    $this->tracker->shouldReceive('disclose')->once()->with($story);

    //
    $story->shouldReceive('hasException')->once()->andReturn(false);

    $promise = null;
    if ($reporter instanceof ReportQueryStub) {
        $promise = mock(PromiseInterface::class);
        $story->shouldReceive('promise')->andReturn($promise);
    }

    $result = $reporter->relay($object);

    if ($result instanceof PromiseInterface) {
        expect($result)->toBe($promise);
    }

})->with('reporter')
    ->with('message');

it('raise exception when message is not marked as handled', function (string $reporterClass, object $object) {
    $reporter = new $reporterClass($this->tracker);
    $message = new Message($object, [Header::EVENT_TYPE => 'foo']);

    // story
    $story = mock(MessageStory::class);
    $this->tracker->shouldReceive('newStory')->once()->with(Reporter::DISPATCH_EVENT)->andReturn($story);
    $story->shouldReceive('withTransientMessage')->once()->with($object);

    // dispatch
    $this->tracker->shouldReceive('disclose')->once()->with($story);
    $story->shouldReceive('isHandled')->once()->andReturn(false);
    $story->shouldReceive('message')->andReturn($message);
    $story->shouldReceive('withRaisedException')->with(Mockery::type(MessageNotHandled::class));
    $story->shouldReceive('stop')->once()->with(false);

    // finalize
    $story->shouldReceive('withEvent')->once()->with(Reporter::FINALIZE_EVENT);
    $this->tracker->shouldReceive('disclose')->once()->with($story);

    //
    $story->shouldReceive('hasException')->once()->andReturn(true);
    $story->shouldReceive('exception')->once()->andReturn(
        MessageNotHandled::withMessageName('foo')
    );

    $reporter->relay($object);
})->throws(MessageNotHandled::class, 'Message with name foo was not handled')
    ->with('reporter')
    ->with('message');

it('raise exception when story hold exception', function (string $reporterClass, object $object, Throwable $exception) {
    $reporter = new $reporterClass($this->tracker);
    $message = new Message($object);

    // story
    $story = mock(MessageStory::class);
    $this->tracker->shouldReceive('newStory')->once()->with(Reporter::DISPATCH_EVENT)->andReturn($story);
    $story->shouldReceive('withTransientMessage')->once()->with($object);

    // dispatch
    $this->tracker->shouldReceive('disclose')->once()->with($story);
    $story->shouldReceive('isHandled')->once()->andReturn(true);
    $story->shouldReceive('message')->andReturn($message);
    $story->shouldReceive('withRaisedException')->with($exception);
    $story->shouldReceive('stop')->once()->with(false);

    // finalize
    $story->shouldReceive('withEvent')->once()->with(Reporter::FINALIZE_EVENT);
    $this->tracker->shouldReceive('disclose')->once()->with($story);

    //
    $story->shouldReceive('hasException')->once()->andReturn(true);
    $story->shouldReceive('exception')->once()->andReturn($exception);

    try {
        $reporter->relay($object);
    } catch (Throwable $e) {
        expect($e)->toBe($exception);
    }

})->with('reporter')
    ->with('message')
    ->with(fn () => [
        new RuntimeException('foo'),
        new InvalidArgumentException('bar'),
    ]);
