<?php

declare(strict_types=1);

namespace Storm\Chronicler\Attribute\Subscriber;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Storm\Annotation\Reference\ReferenceResolverTrait;
use Storm\Chronicler\StreamListener;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\ChroniclerDecorator;
use Storm\Contract\Chronicler\EventableChronicler;
use Storm\Contract\Tracker\Listener;

use function fnmatch;

class StreamSubscriberMap
{
    use ReferenceResolverTrait;

    /**
     * @var Collection{string, array<StreamSubscriberAttribute>}
     */
    protected Collection $entries;

    public function __construct(
        protected StreamSubscriberLoader $loader,
        protected Application $app
    ) {
        $this->entries = new Collection();
    }

    public function load(array $chroniclers): void
    {
        $this->entries = $this->loader->getAttributes();

        $this->whenResolveChronicler($chroniclers);
    }

    public function getEntries(): Collection
    {
        return $this->entries;
    }

    protected function whenResolveChronicler(array $chroniclerIds): void
    {
        foreach ($chroniclerIds as $chroniclerId) {
            $this->app->resolving($chroniclerId, function (Chronicler $chronicler) use ($chroniclerId) {
                if (! $chronicler instanceof EventableChronicler) {
                    return;
                }

                $realInstance = $this->getRealInstance($chronicler);
                $streamSubscribers = $this->getStreamSubscribers($chroniclerId, $realInstance);

                $tracker = $chronicler->getStreamTracker();

                foreach ($streamSubscribers as $streamSubscriber) {
                    $tracker->listen($streamSubscriber);
                }
            });
        }
    }

    /**
     * @return array<StreamListener>
     */
    protected function getStreamSubscribers(string $chroniclerId, Chronicler $chronicler): array
    {
        return $this->getEntries()
            ->filter(fn (StreamSubscriberAttribute $attribute) => $this->matchChronicler($chroniclerId, $attribute->chroniclers, $attribute->autowire))
            ->map(fn (StreamSubscriberAttribute $attribute) => $this->makeListener($attribute, $chronicler))
            ->toArray();
    }

    protected function makeListener(StreamSubscriberAttribute $attribute, Chronicler $chronicler): Listener
    {
        $instance = $this->makeInstance($attribute, $chronicler);

        return new StreamListener($attribute->event, $instance, $attribute->priority);
    }

    protected function makeInstance(StreamSubscriberAttribute $attribute, Chronicler $chronicler): callable
    {
        $parameters = $this->makeParametersFromConstructor($attribute->references);

        $instance = $this->app->make($attribute->subscriberClass, ...$parameters);

        return $instance->{$attribute->method}($chronicler);
    }

    protected function matchChronicler(string $chroniclerId, array $chroniclers, bool $autowire): bool
    {
        foreach ($chroniclers as $chronicler) {
            if (fnmatch($chronicler, $chroniclerId) && $autowire) {
                return true;
            }
        }

        return false;
    }

    protected function getRealInstance(Chronicler $chronicler): Chronicler
    {
        while ($chronicler instanceof ChroniclerDecorator) {
            $chronicler = $chronicler->innerChronicler();
        }

        return $chronicler;
    }

    protected function app(string $serviceId): mixed
    {
        return $this->app[$serviceId];
    }
}
