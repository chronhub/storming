<?php

declare(strict_types=1);

namespace Storm\Reporter\Exception;

use RuntimeException;

class MessageNotHandled extends RuntimeException
{
    public static function withMessageName(string $messageName): self
    {
        return new self("Message with name $messageName was not handled");
    }
}
