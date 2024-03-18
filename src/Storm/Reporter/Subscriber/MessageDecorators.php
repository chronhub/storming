<?php

declare(strict_types=1);

namespace Storm\Reporter\Subscriber;

use Closure;
use Storm\Annotation\Reference\Reference;
use Storm\Contract\Message\MessageDecorator;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\MessageStory;
use Storm\Reporter\Attribute\Subscriber\AsReporterSubscriber;

#[AsReporterSubscriber(
    supports: ['*'],
    event: Reporter::DISPATCH_EVENT,
    priority: 98000,
    autowire: true,
)]
final readonly class MessageDecorators
{
    public function __construct(
        #[Reference('message.decorator.chain.default')] private MessageDecorator $messageDecorator
    ) {
    }

    public function __invoke(): Closure
    {
        return function (MessageStory $story): void {
            $message = $this->messageDecorator->decorate($story->message());

            $story->withMessage($message);
        };
    }
}
