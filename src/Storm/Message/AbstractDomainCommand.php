<?php

declare(strict_types=1);

namespace Storm\Message;

use Storm\Contract\Message\DomainCommand;

abstract class AbstractDomainCommand implements DomainCommand
{
    use HasConstructableContent;
    use WriteHeaders;

    public function type(): DomainType
    {
        return DomainType::COMMAND;
    }
}
