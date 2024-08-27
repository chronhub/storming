<?php

declare(strict_types=1);

namespace Storm\Story\Support;

use Storm\Contract\Message\DomainCommand;
use Storm\Contract\Message\EventHeader;
use Storm\Contract\Message\Header;
use Storm\Contract\Message\MessageDecorator;
use Storm\Message\Message;

use function get_class;

/**
 * Causation Command Decorator decorates a message with the causation id and causation type of the command.
 * Useful for debugging but required for all commands to be logged.
 */
final readonly class CausationCommandDecorator implements MessageDecorator
{
    public function __construct(private DomainCommand $command) {}

    public function decorate(Message $message): Message
    {
        if ($message->hasNot(EventHeader::EVENT_CAUSATION_ID)
            && $message->hasNot(EventHeader::EVENT_CAUSATION_TYPE)) {
            $message = $message->withHeader(
                EventHeader::EVENT_CAUSATION_ID,
                $this->command->header(Header::EVENT_ID)
            );

            $message = $message->withHeader(EventHeader::EVENT_CAUSATION_TYPE, get_class($this->command));
        }

        return $message;
    }
}
