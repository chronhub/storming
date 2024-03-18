<?php

declare(strict_types=1);

namespace Storm\Chronicler\Exceptions;

use Storm\Contract\Chronicler\ChroniclerFailed;

class InvalidArgumentException extends \InvalidArgumentException implements ChroniclerFailed
{
}
