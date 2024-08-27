<?php

declare(strict_types=1);

namespace Storm\Tests\Stubs;

use Storm\Contract\Projector\ProjectionModel;

final readonly class DummyProjectionModel implements ProjectionModel
{
    private string $state;

    private string $checkpoint;

    public function __construct(
        private string $name,
        private string $status,
        ?string $state,
        ?string $checkpoint,
        private ?string $lockedUntil
    ) {
        $this->state = $state ?? '{}';
        $this->checkpoint = $checkpoint ?? '{}';
    }

    public function name(): string
    {
        return $this->name;
    }

    public function state(): string
    {
        return $this->state;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function checkpoint(): string
    {
        return $this->checkpoint;
    }

    public function lockedUntil(): ?string
    {
        return $this->lockedUntil;
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'status' => $this->status,
            'state' => $this->state,
            'checkpoint' => $this->checkpoint,
            'locked_until' => $this->lockedUntil,
        ];
    }
}
