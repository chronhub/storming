<?php

declare(strict_types=1);

namespace Storm\Chronicler\Exceptions;

use Storm\Contract\Chronicler\ChroniclerError;
use UnexpectedValueException;

class UnexpectedCallback extends UnexpectedValueException implements ChroniclerError
{
}
