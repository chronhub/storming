<?php

declare(strict_types=1);

namespace Storm\Reporter\Attribute;

readonly class ReporterQueue
{
    public function __construct(
        public string $id,
        public Mode $mode,
        public null|string|array $default
    ) {
    }
}
