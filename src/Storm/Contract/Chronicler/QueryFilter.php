<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

interface QueryFilter
{
    /**
     * Apply the filter to the query.
     */
    public function apply(): callable;
}
