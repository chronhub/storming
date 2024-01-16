<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Throwable;

use function array_shift;

trait DelegateToQueue
{
    /**
     * @var array <empty|object|array>
     */
    private array $queue = [];

    private bool $isDispatching = false;

    /**
     * Queue and process one message at a time.
     *
     * @throws Throwable
     */
    protected function queueAndProcess(object|array $message): void
    {
        $this->queue[] = $message;

        if (! $this->isDispatching) {
            $this->isDispatching = true;

            try {
                while ($command = array_shift($this->queue)) {
                    $this->dispatch($command);
                }
            } finally {
                $this->isDispatching = false;
            }
        }
    }
}
