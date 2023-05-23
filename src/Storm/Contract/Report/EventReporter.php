<?php

declare(strict_types=1);

namespace Storm\Contract\Report;

interface EventReporter extends Reporter
{
    public function relay(object|array $message): void;
}
