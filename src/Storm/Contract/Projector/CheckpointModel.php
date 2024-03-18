<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

interface CheckpointModel
{
    public function id(): string;

    public function projectionName(): string;

    public function streamName(): string;

    public function position(): int;

    public function eventTime(): string;

    public function createdAt(): string;

    public function gaps(): string;
}
