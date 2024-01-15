<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Generator;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\Listener;
use Storm\Contract\Tracker\MessageStory;
use Storm\Contract\Tracker\MessageTracker;
use Storm\Tracker\TrackMessage;
use Throwable;
use TypeError;

use function is_string;

trait HasConstructableReporter
{
    protected ?Container $container = null;

    public function __construct(
        protected readonly ?MessageTracker $tracker = new TrackMessage()
    ) {
    }

    public function subscribe(object|string ...$messageSubscribers): void
    {
        foreach ($this->resolveSubscriber($messageSubscribers) as $listener) {
            $this->tracker->listen($listener);
        }
    }

    public function tracker(): MessageTracker
    {
        return $this->tracker;
    }

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    protected function dispatch(array|object $message): MessageStory
    {
        $story = $this->tracker->newStory(Reporter::DISPATCH_EVENT);

        $story->withTransientMessage($message);

        try {
            $this->dispatchStory($story);
        } catch (Throwable $exception) {
            $story->withRaisedException($exception);
        } finally {
            $this->finalizeStory($story);
        }

        if ($story->hasException()) {
            throw $story->exception();
        }

        return $story;
    }

    protected function dispatchStory(MessageStory $story): void
    {
        $this->tracker->disclose($story);

        if (! $story->isHandled()) {
            $messageName = $this->determineMessageName($story);

            throw MessageNotHandled::withMessageName($messageName);
        }
    }

    protected function finalizeStory(MessageStory $story): void
    {
        $story->stop(false);

        $story->withEvent(Reporter::FINALIZE_EVENT);

        $this->tracker->disclose($story);
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

    protected function resolveSubscriber(array $subscribers): Generator
    {
        foreach ($subscribers as $subscriber) {
            if ($subscriber instanceof Listener) {
                yield $subscriber;
            } elseif (is_string($subscriber) && $this->container && $this->container->has($subscriber)) {
                yield from $this->container[$subscriber];
            } else {
                throw new InvalidArgumentException('Unable to resolve subscriber');
            }
        }
    }
}
