<?php

declare(strict_types=1);

namespace Storm\Projector\Connector;

use Illuminate\Contracts\Events\Dispatcher;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\ChroniclerDecorator;
use Storm\Contract\Chronicler\DatabaseChronicler;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Projector\Exception\ConfigurationViolation;
use Storm\Projector\Options\Option;
use Storm\Projector\Options\OptionResolver;

use function get_class;
use function sprintf;

final readonly class DatabaseConnectionManager implements ConnectionManager
{
    private DatabaseChronicler $chronicler;

    public function __construct(
        DatabaseChronicler $chronicler,
        private EventStreamProvider $eventStreamProvider,
        private ProjectionProvider $projectionProvider,
        private QueryFilter $queryFilter,
        private SystemClock $clock,
        private SymfonySerializer $serializer,
        private Option|array $options,
        private ?Dispatcher $dispatcher = null,
    ) {
        while ($chronicler instanceof ChroniclerDecorator) {
            $chronicler = $chronicler->innerChronicler();
        }

        if (! $chronicler instanceof DatabaseChronicler) {
            throw ConfigurationViolation::message(sprintf(
                'Chronicler must be an instance of %s, got %s', DatabaseChronicler::class, get_class($chronicler)
            ));
        }

        $this->chronicler = $chronicler;
    }

    public function toOption(array $options = []): Option
    {
        $resolver = new OptionResolver($this->options);

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

    public function queryFilter(): QueryFilter
    {
        return $this->queryFilter;
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
