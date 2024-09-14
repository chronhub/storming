<?php

declare(strict_types=1);

namespace Storm\Message;

use Storm\Contract\Message\MessageConverter;
use Storm\Contract\Message\MessageFactory;

final readonly class ComplianceMessageConverter implements MessageConverter
{
    public function __construct(private MessageFactory $messageFactory) {}

    public function convert(array|object $message): Message
    {
        return $this->messageFactory->createMessageFrom($message);
    }
}
