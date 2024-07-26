<?php

declare(strict_types=1);

namespace Storm\Aggregate\Attribute;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Storm\Annotation\Reference\ReferenceResolverTrait;
use Storm\Stream\StreamName;

class AggregateRepositoryMap
{
    use ReferenceResolverTrait;

    /**
     * @var Collection{string, array<AggregateRepositoryAttribute>}
     */
    protected Collection $entries;

    public function __construct(
        protected AggregateRepositoryLoader $loader,
        protected Application $app
    ) {
        $this->entries = new Collection();
    }

    public function load(): void
    {
        $this->entries = $this->loader->getAttributes()->each(fn ($attribute) => $this->bind($attribute));
    }

    public function getEntries(): Collection
    {
        return $this->entries;
    }

    protected function bind(AggregateRepositoryAttribute $attribute): void
    {
        $factory = $this->getFactory($attribute->factory);

        $this->app->bind($attribute->abstract, function (Application $app) use ($factory, $attribute) {

            // todo support type,
            //  do we need reference here?
            $repository = $factory->makeRepository(
                $app[$attribute->chronicler],
                new StreamName($attribute->streamName),
                $app[$attribute->messageDecorator]
            );

            return new $attribute->repository($repository);
        });
    }

    protected function getFactory(string $factory): AggregateRepositoryFactory
    {
        return $this->app[$factory];
    }

    protected function app(string $serviceId): mixed
    {
        return $this->app[$serviceId];
    }
}
