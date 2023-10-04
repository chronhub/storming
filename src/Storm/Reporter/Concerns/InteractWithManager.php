<?php

declare(strict_types=1);

namespace Storm\Reporter\Concerns;

trait InteractWithManager
{
    protected function createInstance(string $class, array $references): object
    {
        $parameters = $references['__construct'] ?? [];

        if ($parameters !== []) {
            foreach ($parameters as $name => $binding) {
                $parameters[$name] = $this->container[$binding];
            }

            return $this->container->make($class, $parameters);
        }

        return $this->container[$class];
    }
}
