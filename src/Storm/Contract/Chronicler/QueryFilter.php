<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

interface QueryFilter
{
    public function apply(): callable;
}
