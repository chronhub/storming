<?php

declare(strict_types=1);

namespace Storm\Support\Message;

use Carbon\Carbon;
use Closure;
use Storm\Contract\Message\Header;
use Storm\Contract\Tracker\MessageStory;
use Symfony\Component\Uid\Uuid;

final class MessageDecoratorSubscriber
{
    public function __invoke(): Closure
    {
        return function (MessageStory $story): void {
            $message = $story->message();

            if ($message->hasNot(Header::EVENT_ID)) {
                $message = $message->withHeader(Header::EVENT_ID, Uuid::v4()->jsonSerialize());
            }

            if ($message->hasNot(Header::EVENT_TIME)) {
                $message = $message->withHeader(Header::EVENT_TIME, Carbon::now('UTC')->format('Y-m-d\TH:i:s.u'));
            }

            if ($message->hasNot(Header::EVENT_TYPE)) {
                $message = $message->withHeader(Header::EVENT_TYPE, $message->name());
            }

            if ($message->hasNot(Header::EVENT_DISPATCHED)) {
                $message = $message->withHeader(Header::EVENT_DISPATCHED, false);
            }

            $story->withMessage($message);
        };
    }
}
