<?php

declare(strict_types=1);

namespace Storm\Support\Attribute;

use Storm\Contract\Reporter\MessageFilter;
use Storm\Contract\Reporter\Reporter;

final readonly class ReporterInstance
{
    public function __construct(
        public Reporter $reporter,
        public string $name,
        public MessageFilter $messageFilter
    ) {
    }
}
