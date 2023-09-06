<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Storm\Contract\Message\Header;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\MessageStory;
use Storm\Contract\Tracker\MessageTracker;
use Storm\Tracker\TrackMessage;
use Throwable;

trait HasProcessStory
{
    public function __construct(readonly ?MessageTracker $tracker = new TrackMessage())
    {
    }

    // todo : do we keep string subscriber as they should already be resolved by container?
    public function subscribe(object|string ...$messageSubscribers): void
    {
        foreach ($messageSubscribers as $messageSubscriber) {
            $this->tracker->watch($messageSubscriber);
        }
    }

    // todo: remove this method
    public function tracker(): MessageTracker
    {
        return $this->tracker;
    }

    protected function dispatch(object|array $message): MessageStory
    {
        $story = $this->tracker->newStory(self::DISPATCH_EVENT);

        $story->withTransientMessage($message);

        $this->process($story);

        if ($story->hasException()) {
            throw $story->exception();
        }

        return $story;
    }

    private function process(MessageStory $story): void
    {
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
    }

    private function determineMessageName(MessageStory $story): string
    {
        return $story->message()->header(Header::EVENT_TYPE) ?? $story->message()->event()::class;
    }
}
