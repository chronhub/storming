<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Storm\Contract\Message\Header;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\MessageStory;
use Storm\Contract\Tracker\MessageSubscriber;
use Storm\Contract\Tracker\MessageTracker;
use Throwable;

trait HasConstructableReporter
{
    public function __construct(public readonly MessageTracker $tracker)
    {
    }

    protected function relayMessage(MessageStory $story): void
    {
        try {
            $this->tracker->disclose($story);

            if (! $story->isHandled()) {
                $messageName = $story->message()->header(Header::EVENT_TYPE) ?? $story->message()->event()::class;

                throw MessageNotHandled::withMessageName($messageName);
            }
        } catch (Throwable $exception) {
            $story->withRaisedException($exception);
        } finally {
            $story->stop(false);

            $story->withEvent(Reporter::FINALIZE_EVENT);

            $this->tracker->disclose($story);
        }
    }

    public function subscribe(MessageSubscriber ...$messageSubscribers): void
    {
        foreach ($messageSubscribers as $messageSubscriber) {
            $messageSubscriber->attachTo($this->tracker);
        }
    }

    public function tracker(): MessageTracker
    {
        return $this->tracker;
    }
}
