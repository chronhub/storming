<?php

declare(strict_types=1);

namespace Storm\Chronicler;

use Storm\Chronicler\Exceptions\ConcurrencyException;
use Storm\Chronicler\Exceptions\StreamAlreadyExists;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Chronicler\Exceptions\UnexpectedCallback;
use Storm\Contract\Message\MessageDecorator;
use Storm\Contract\Tracker\StreamStory;
use Storm\Message\Message;
use Storm\Stream\Stream;
use Storm\Tracker\InteractWithStory;

class StreamDraft implements StreamStory
{
    use InteractWithStory;

    /**
     * @var callable
     */
    private $callback;

    public function deferred(callable $callback): void
    {
        $this->callback = $callback;
    }

    public function promise(): mixed
    {
        if ($this->callback === null) {
            throw new UnexpectedCallback('No event callback has been set');
        }

        return ($this->callback)();
    }

    public function decorate(MessageDecorator $messageDecorator): void
    {
        $stream = $this->promise();

        if (! $stream instanceof Stream) {
            throw new UnexpectedCallback('No stream has been set as event callback');
        }

        $events = [];

        foreach ($stream->events() as $streamEvent) {
            $events[] = $messageDecorator->decorate(new Message($streamEvent))->event();
        }

        $this->deferred(static fn (): Stream => new Stream($stream->name(), $events));
    }

    public function hasStreamNotFound(): bool
    {
        // checkMe check behaviour with NoStreamEventReturn
        return $this->exception instanceof StreamNotFound;
    }

    public function hasStreamAlreadyExits(): bool
    {
        return $this->exception instanceof StreamAlreadyExists;
    }

    public function hasConcurrency(): bool
    {
        return $this->exception instanceof ConcurrencyException;
    }
}
