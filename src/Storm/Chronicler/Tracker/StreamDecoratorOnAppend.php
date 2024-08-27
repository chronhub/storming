<?php

declare(strict_types=1);

namespace Storm\Chronicler\Tracker;

use Closure;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\MessageDecorator;
use Storm\Message\Message;
use Storm\Stream\Stream;

use function array_map;
use function iterator_to_array;

final readonly class StreamDecoratorOnAppend
{
    public function __construct(private MessageDecorator $eventDecorator) {}

    public function __invoke(): Closure
    {
        return function (Stream $stream): Stream {
            $streamEvents = array_map(
                fn (DomainEvent $event) => $this->eventDecorator->decorate(new Message($event))->event(),
                iterator_to_array($stream->events())
            );

            return new Stream($stream->name(), $streamEvents);
        };
    }
}
