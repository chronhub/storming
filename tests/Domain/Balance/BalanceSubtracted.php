<?php

declare(strict_types=1);

namespace Storm\Tests\Domain\Balance;

use Storm\Message\DomainEvent;

final class BalanceSubtracted extends DomainEvent
{
    public static function withBalance(BalanceId $balanceId, int $amount): self
    {
        return new self([
            'id' => $balanceId->toString(),
            'amount' => $amount,
        ]);
    }

    public function id(): string
    {
        return $this->content['id'];
    }

    public function amount(): int
    {
        return $this->content['amount'];
    }
}
