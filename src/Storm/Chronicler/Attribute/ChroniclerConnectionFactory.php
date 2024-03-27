<?php

declare(strict_types=1);

namespace Storm\Chronicler\Attribute;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Connection;
use RuntimeException;
use Storm\Contract\Chronicler\Chronicler;

use function method_exists;
use function sprintf;

class ChroniclerConnectionFactory extends AbstractChroniclerFactory
{
    public function __construct(protected Application $app)
    {
    }

    public function fromAttribute(ChroniclerAttribute $attribute): Chronicler
    {
        $instance = $this->createInstance($attribute);

        if (! $attribute->eventable) {
            return $instance;
        }

        return $this->createDecoratedInstance($instance, $attribute->transactional, $attribute->subscribers);
    }

    protected function createInstance(ChroniclerAttribute $attribute): Chronicler
    {
        $connection = $this->makeConnection($attribute->connection);

        if ($attribute->firstClass === $attribute->chronicler) {
            throw new RuntimeException(sprintf(
                'First class cannot be the same as the chronicler class %s',
                $attribute->chronicler
            ));
        }

        $instanceClass = $attribute->firstClass ?? $attribute->chronicler;

        $instance = new $instanceClass(
            $connection,
            $this->app[$attribute->evenStreamProvider],
            $this->app[$attribute->persistence],
            $this->app[$attribute->streamEventLoader],
            $attribute->tableName
        );

        if ($attribute->firstClass !== null) {
            $instance = new $attribute->chronicler($instance);

            if (method_exists($instance, 'setConnection')) {
                $instance->setConnection($connection);
            }
        }

        return $instance;
    }

    protected function makeConnection(string $connection): Connection
    {
        return $this->app['db']->connection($connection);
    }

    protected function app(): Application
    {
        return $this->app;
    }
}
