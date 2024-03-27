<?php

declare(strict_types=1);

namespace Storm\Chronicler\Attribute;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use RuntimeException;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Reporter\Attribute\ReporterQueue;

use function sprintf;

class ChroniclerMap
{
    public const string ERROR_CHRONICLER_ALREADY_EXISTS = 'Chronicler %s already exists';

    /**
     * @var Collection<array<string, ChroniclerAttribute>>
     */
    protected Collection $entries;

    /**
     * @var array<ReporterQueue>
     */
    protected array $queues = [];

    public function __construct(
        protected ChroniclerLoader $loader,
        protected Application $app
    ) {
        $this->entries = new Collection();
    }

    public function load(): void
    {
        $this->loader
            ->getAttributes()
            ->each(function (ChroniclerAttribute $attribute): void {
                $this->makeEntry($attribute);

                $this->bind($attribute);
            });
    }

    public function getEntries(): Collection
    {
        return $this->entries;
    }

    protected function makeEntry(ChroniclerAttribute $attribute): void
    {
        if ($this->entries->has($attribute->abstract)) {
            throw new RuntimeException(sprintf(self::ERROR_CHRONICLER_ALREADY_EXISTS, $attribute->abstract));
        }

        $this->entries->put($attribute->abstract, $attribute);
    }

    protected function bind(ChroniclerAttribute $attribute): void
    {
        $this->app->bind($attribute->abstract, fn (): Chronicler => $this->makeInstance($attribute));
    }

    protected function makeInstance(ChroniclerAttribute $attribute): Chronicler
    {
        $chroniclerFactory = $this->createFromFactory($attribute->factory);

        return $chroniclerFactory->fromAttribute($attribute);
    }

    protected function createFromFactory(string $decoratorFactory): ChroniclerConnectionFactory
    {
        return $this->app[$decoratorFactory];
    }
}
