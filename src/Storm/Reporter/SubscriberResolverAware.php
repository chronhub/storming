<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Storm\Contract\Tracker\Listener;

use function is_string;

class SubscriberResolverAware
{
    public function __construct(private readonly Container $container)
    {
    }

    public function __invoke(string|object $subscriber)
    {
        if (is_string($subscriber) && $this->container->has($subscriber)) {
            return $this->container[$subscriber];
        }

        if ($subscriber instanceof Listener) {
            return $subscriber;
        }

        throw new InvalidArgumentException('Unable to resolve subscriber');
    }
}
