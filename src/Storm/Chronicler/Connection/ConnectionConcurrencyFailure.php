<?php

declare(strict_types=1);

namespace Storm\Chronicler\Connection;

use Storm\Chronicler\Exceptions\ConcurrencyException;

class ConnectionConcurrencyFailure extends ConcurrencyException
{
}
