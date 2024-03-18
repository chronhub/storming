<?php

declare(strict_types=1);

namespace Storm\Chronicler\Exceptions;

use Storm\Contract\Chronicler\ChroniclerFailed;

class RuntimeException extends \RuntimeException implements ChroniclerFailed
{
}
