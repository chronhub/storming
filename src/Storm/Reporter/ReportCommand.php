<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Storm\Contract\Reporter\CommandReporter;
use Storm\Message\Message;

use function array_shift;

final class ReportCommand implements CommandReporter
{
    use HasConstructableReporter;

    private bool $isDispatching = false;

    /**
     * @var array<Message>
     */
    private array $commandQueue = [];

    public function relay(object|array $message): void
    {
        $this->commandQueue[] = $message;

        if (! $this->isDispatching) {
            $this->isDispatching = true;

            try {
                while ($command = array_shift($this->commandQueue)) {
                    $story = $this->tracker->newStory(self::DISPATCH_EVENT);

                    $story->withTransientMessage($command);

                    $this->relayMessage($story);

                    if ($story->hasException()) {
                        throw $story->exception();
                    }
                }
            } finally {
                $this->isDispatching = false;
            }
        }
    }
}
