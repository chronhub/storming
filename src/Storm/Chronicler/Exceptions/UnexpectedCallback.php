<?php

declare(strict_types=1);

namespace Storm\Chronicler\Exceptions;

use Storm\Contract\Chronicler\ChroniclerFailed;
use UnexpectedValueException;

class UnexpectedCallback extends UnexpectedValueException implements ChroniclerFailed
{
}
