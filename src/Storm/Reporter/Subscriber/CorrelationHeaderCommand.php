<?php

declare(strict_types=1);

namespace Storm\Reporter\Subscriber;

use Closure;
use Storm\Contract\Chronicler\EventableChronicler;
use Storm\Contract\Message\DomainCommand;
use Storm\Contract\Message\EventHeader;
use Storm\Contract\Message\Header;
use Storm\Contract\Message\MessageDecorator;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\MessageStory;
use Storm\Contract\Tracker\StreamStory;
use Storm\Message\Message;
use Storm\Reporter\Attribute\Subscriber\AsReporterSubscriber;

final class CorrelationHeaderCommand
{
    private ?DomainCommand $command = null;

    #[AsReporterSubscriber(
        supports: ['reporter.command.*'],
        event: Reporter::DISPATCH_EVENT,
        priority: 97000,
        autowire: true,
    )]
    public function setCommand(): Closure
    {
        return function (MessageStory $story): void {
            $command = $story->message()->event();

            if ($command instanceof DomainCommand) {
                $this->command = $command;
            }
        };
    }

    #[AsReporterSubscriber(
        supports: ['reporter.command.*'],
        event: Reporter::FINALIZE_EVENT,
        priority: 1000,
        autowire: true,
    )]
    public function eraseCommand(): Closure
    {
        return function (): void {
            $this->command = null;
        };
    }

    #[AsStreamSubscriber(
        event: EventableChronicler::APPEND_STREAM_EVENT,
        chronicler: 'chronicler.event.*',
        priority: 100
    )]
    public function onAppendStream(): Closure
    {
        return function (StreamStory $story): void {
            if ($this->command === null) {
                return;
            }

            $streamDecorator = $this->addCorrelationHeader($this->command);

            $story->decorate($streamDecorator);
        };
    }

    private function addCorrelationHeader(DomainCommand $command): MessageDecorator
    {
        $eventId = $command->header(Header::EVENT_ID);
        $eventType = $command->header(Header::EVENT_TYPE);

        return new class($eventId, $eventType) implements MessageDecorator
        {
            public function __construct(
                private readonly string $eventId,
                private readonly string $eventType
            ) {
            }

            public function decorate(Message $message): Message
            {
                if ($message->has(EventHeader::EVENT_CAUSATION_ID) && $message->has(EventHeader::EVENT_CAUSATION_TYPE)) {
                    return $message;
                }

                $causationHeaders = [
                    EventHeader::EVENT_CAUSATION_ID => $this->eventId,
                    EventHeader::EVENT_CAUSATION_TYPE => $this->eventType,
                ];

                return $message->withHeaders($message->headers() + $causationHeaders);
            }
        };
    }
}
