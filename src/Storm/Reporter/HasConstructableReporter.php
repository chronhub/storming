<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Generator;
use LogicException;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\Listener;
use Storm\Contract\Tracker\MessageStory;
use Storm\Contract\Tracker\MessageTracker;
use Storm\Tracker\TrackMessage;
use Throwable;
use TypeError;

trait HasConstructableReporter
{
    /**
     * @var callable|null
     */
    protected $subscriberResolver = null;

    public function __construct(protected readonly ?MessageTracker $tracker = new TrackMessage())
    {
    }

    public function subscribe(object|string ...$messageSubscribers): void
    {
        foreach ($this->resolveSubscriber(...$messageSubscribers) as $subscriber) {
            $this->tracker->listen($subscriber);
        }
    }

    public function withSubscriberResolver(callable $subscriberResolver): void
    {
        if ($this->subscriberResolver !== null) {
            throw new LogicException('Subscriber resolver is already set');
        }

        $this->subscriberResolver = $subscriberResolver;
    }

    public function tracker(): MessageTracker
    {
        return $this->tracker;
    }

    protected function dispatch(object|array $message): MessageStory
    {
        $story = $this->tracker->newStory(self::DISPATCH_EVENT);

        $story->withTransientMessage($message);

        try {
            $this->tracker->disclose($story);

            if (! $story->isHandled()) {
                $messageName = $this->determineMessageName($story);

                throw MessageNotHandled::withMessageName($messageName);
            }
        } catch (Throwable $exception) {
            $story->withRaisedException($exception);
        } finally {
            $story->stop(false);

            $story->withEvent(Reporter::FINALIZE_EVENT);

            $this->tracker->disclose($story);
        }

        if ($story->hasException()) {
            throw $story->exception();
        }

        return $story;
    }

    /**
     * @return Generator<Listener>
     */
    protected function resolveSubscriber(string|object ...$subscribers): Generator
    {
        foreach ($subscribers as $subscriber) {
            yield from ($this->subscriberResolver)($subscriber);
        }
    }

    /**
     * @return non-empty-string
     */
    protected function determineMessageName(MessageStory $story): string
    {
        try {
            return $story->message()->name();
        } catch (TypeError) {
            return 'undefined';
        }
    }
}
