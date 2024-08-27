<?php

declare(strict_types=1);

namespace Storm\Tests\Domain\Balance;

use Storm\Message\DomainEvent;

final class BalanceNoOp extends DomainEvent
{
    public static function withBalance(BalanceId $balanceId): self
    {
        return new self(['id' => $balanceId->toString()]);
    }

    public function id(): string
    {
        return $this->content['id'];
    }
}
