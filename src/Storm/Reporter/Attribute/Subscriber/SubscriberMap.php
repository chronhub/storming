<?php

declare(strict_types=1);

namespace Storm\Reporter\Attribute\Subscriber;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Storm\Annotation\Reference\ReferenceResolverTrait;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\Listener;
use Storm\Reporter\Subscriber\NameReporter;
use Storm\Tracker\GenericListener;

class SubscriberMap
{
    use ReferenceResolverTrait;

    /**
     * @var Collection{string, array<SubscriberAttribute>}
     */
    protected Collection $entries;

    public function __construct(
        protected SubscriberLoader $loader,
        protected Application $app
    ) {
        $this->entries = new Collection();
    }

    public function load(array $reporters): void
    {
        $this->entries = $this->loader
            ->getAttributes()
            ->map(fn (SubscriberAttribute $attribute) => $this->build($attribute));

        $this->whenResolveReporter($reporters);
    }

    public function getEntries(): Collection
    {
        return $this->entries;
    }

    protected function build(SubscriberAttribute $attribute): SubscriberHandler
    {
        $alias = $this->formatAlias($attribute->alias, $attribute->className, $attribute->method);

        $listener = $this->makeListener($attribute);

        return new SubscriberHandler($alias, $attribute->supports, $listener, $attribute->autowire);
    }

    protected function formatAlias(?string $alias, string $class, string $method): string
    {
        // todo deal with multiple reporters, as they should be bound
        //  tags all subscribers id with alias per reporter.id.subscribers
        //  fetch and resolve when a reporter is resolved
        //  this will allow for multiple reporters to be resolved
        return $alias ?? $class.'@'.$method;
    }

    protected function makeListener(SubscriberAttribute $attribute): Listener
    {
        $parameters = $this->makeParametersFromConstructor($attribute->references);

        $instance = $this->app->make($attribute->className, ...$parameters);

        return new GenericListener($attribute->event, $instance->{$attribute->method}(...), $attribute->priority);
    }

    protected function whenResolveReporter(array $reporterIds): void
    {
        foreach ($reporterIds as $reporterId) {
            $this->app->resolving($reporterId, function (Reporter $reporter) use ($reporterId) {
                $listeners = $this->filterSubscribersForReporter($reporterId);

                foreach ($listeners as $subscriber) {
                    $reporter->subscribe($subscriber);
                }

                // todo where to deal with this
                $reporter->subscribe(new GenericListener(Reporter::DISPATCH_EVENT, new NameReporter($reporterId), 99000));
            });
        }
    }

    /**
     * @return array<Listener>
     */
    protected function filterSubscribersForReporter(string $reporter): array
    {
        return $this->getEntries()
            ->filter(fn (SubscriberHandler $handler) => $handler->match($reporter))
            ->map(fn (SubscriberHandler $handler) => $handler->listener)
            ->toArray();
    }

    protected function app(string $serviceId): mixed
    {
        return $this->app[$serviceId];
    }
}
