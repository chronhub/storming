<?php

declare(strict_types=1);

namespace Storm\Reporter\Subscriber;

use Closure;
use Storm\Annotation\Reference\Reference;
use Storm\Contract\Message\MessageFactory;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\MessageStory;
use Storm\Reporter\Attribute\Subscriber\AsReporterSubscriber;

#[AsReporterSubscriber(
    supports: ['*'],
    event: Reporter::DISPATCH_EVENT,
    priority: 100000,
    autowire: true,
)]
final readonly class MakeMessage
{
    public function __construct(
        #[Reference('message.factory.default')] private MessageFactory $messageFactory
    ) {}

    public function __invoke(): Closure
    {
        return function (MessageStory $story): void {
            $message = $this->messageFactory->createMessageFrom($story->pullTransientMessage());

            $story->withMessage($message);
        };
    }
}
