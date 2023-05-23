<?php

declare(strict_types=1);

namespace Storm\Contract\Report;

use React\Promise\PromiseInterface;

interface QueryReporter extends Reporter
{
    public function relay(object|array $message): PromiseInterface;
}
