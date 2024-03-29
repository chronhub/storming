<?php

declare(strict_types=1);

namespace Storm\Projector\Repository;

use Storm\Contract\Projector\ProjectionModel;

final class InMemoryProjection implements ProjectionModel
{
    private string $checkpoint = '{}';

    private string $state = '{}';

    private ?string $lockedUntil = null;

    private function __construct(
        private readonly string $name,
        private string $status
    ) {
    }

    public static function create(string $name, string $status): self
    {
        return new self($name, $status);
    }

    public function setCheckpoint(string $checkpoint): void
    {
        $this->checkpoint = $checkpoint;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function setLockedUntil(?string $lockedUntil): void
    {
        $this->lockedUntil = $lockedUntil;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function checkpoint(): string
    {
        return $this->checkpoint;
    }

    public function state(): string
    {
        return $this->state;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function lockedUntil(): ?string
    {
        return $this->lockedUntil;
    }
}
