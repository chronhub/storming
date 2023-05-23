<?php

declare(strict_types=1);

namespace Storm\Contract\Report;

interface CommandReporter extends Reporter
{
    public function relay(object|array $message): void;
}
