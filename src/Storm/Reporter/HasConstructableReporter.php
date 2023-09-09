<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Storm\Contract\Message\Header;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\MessageStory;
use Storm\Contract\Tracker\MessageTracker;
use Storm\Tracker\TrackMessage;
use Throwable;
use TypeError;

trait HasConstructableReporter
{
    public function __construct(readonly ?MessageTracker $tracker = new TrackMessage())
    {
    }

    public function subscribe(object|string ...$messageSubscribers): void
    {
        foreach ($messageSubscribers as $messageSubscriber) {
            $this->tracker->watch($messageSubscriber);
        }
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

    private function determineMessageName(MessageStory $story): string
    {
        try {
            return $story->message()->header(Header::EVENT_TYPE) ?? $story->message()->event()::class;
        } catch (TypeError) {
            return 'undefined';
        }
    }
}
