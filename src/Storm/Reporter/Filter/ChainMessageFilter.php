<?php

declare(strict_types=1);

namespace Storm\Reporter\Filter;

use Storm\Contract\Reporter\MessageFilter;
use Storm\Message\Message;

final readonly class ChainMessageFilter implements MessageFilter
{
    private array $filters;

    public function __construct(MessageFilter ...$filters)
    {
        $this->filters = $filters;
    }

    public function allows(Message $message): bool
    {
        foreach ($this->filters as $filter) {
            if (! $filter->allows($message)) {
                return false;
            }
        }

        return true;
    }
}
