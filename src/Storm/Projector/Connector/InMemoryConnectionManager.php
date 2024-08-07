<?php

declare(strict_types=1);

namespace Storm\Projector\Connector;

use Illuminate\Contracts\Events\Dispatcher;
use RuntimeException;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\ChroniclerDecorator;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Chronicler\InMemoryChronicler;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Projector\Options\Option;
use Storm\Projector\Options\ProjectionOptionResolver;

final class InMemoryConnectionManager implements ConnectionManager
{
    private InMemoryChronicler $chronicler;

    public function __construct(
        InMemoryChronicler $chronicler,
        private readonly EventStreamProvider $eventStreamProvider,
        private readonly ProjectionProvider $projectionProvider,
        private readonly SystemClock $clock,
        private readonly SymfonySerializer $serializer,
        private readonly Option|array $options,
        private readonly ?Dispatcher $dispatcher = null,
    ) {
        while ($chronicler instanceof ChroniclerDecorator) {
            $chronicler = $chronicler->innerChronicler();
        }

        if (! $chronicler instanceof InMemoryChronicler) {
            throw new RuntimeException('Chronicler must be an instance of InMemoryChronicler');
        }

        $this->chronicler = $chronicler;
    }

    public function toProjectionOption(array $options = []): Option
    {
        $resolver = new ProjectionOptionResolver($this->options);

        return $resolver($options);
    }

    public function eventStore(): Chronicler
    {
        return $this->chronicler;
    }

    public function eventStreamProvider(): EventStreamProvider
    {
        return $this->eventStreamProvider;
    }

    public function projectionProvider(): ProjectionProvider
    {
        return $this->projectionProvider;
    }

    public function serializer(): SymfonySerializer
    {
        return $this->serializer;
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }

    public function dispatcher(): ?Dispatcher
    {
        return $this->dispatcher;
    }
}
