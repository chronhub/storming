<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Closure;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Storm\Attribute\Loader;
use Storm\Contract\Reporter\Router;
use Storm\Reporter\Concerns\InteractWithManager;
use Storm\Support\ContainerAsClosure;

use function array_map;
use function class_exists;

final class MessageManager implements Router
{
    use InteractWithManager;

    private array $messages = [];

    private array $instances = [];

    private array $loaded;

    protected Container $container;

    public function __construct(Loader $loader, ContainerAsClosure $container)
    {
        $this->loaded = $loader->getHandlers();
        $this->container = $container->container;
    }

    public function get(string $name): ?array
    {
        if (! class_exists($name)) {
            throw new InvalidArgumentException("Message $name must be a class");
        }

        if (isset($this->messages[$name])) {
            return $this->messages[$name];
        }

        if (! isset($this->loaded[$name])) {
            return null;
        }

        return $this->messages[$name] = $this->resolve($name);
    }

    private function resolve(string $name): array
    {
        $handlers = $this->loaded[$name];

        return $this->toCallable($handlers);
    }

    /**
     * Transform array of handlers to array of callables if necessary
     *
     * @return array<callable>
     */
    private function toCallable(array $handlers): array
    {
        // checkMe do we really need to keep instances in memory?
        // we could also accept methods with many args FirstMessage|SecondMessage in method handler
        return array_map(function (array $handler) {
            $class = $handler['class'];

            if (isset($this->instances[$class])) {
                return $this->instances[$class];
            }

            if (! isset($this->instances[$class])) {
                $this->instances[$class] = $this->createInstance($class, $handler['references']);
            }

            if ($handler['method'] === '__invoke') {
                return $this->instances[$class];
            }

            return Closure::fromCallable([$this->instances[$class], $handler['method']]);
        }, $handlers);
    }
}
