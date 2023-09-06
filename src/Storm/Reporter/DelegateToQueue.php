<?php

declare(strict_types=1);

namespace Storm\Reporter;

use function array_shift;

trait DelegateToQueue
{
    /**
     * @var array <empty|object>
     */
    private array $queue = [];

    private bool $isDispatching = false;

    public function queueAndProcess(object $message): void
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
