<?php

declare(strict_types=1);

namespace Storm\Chronicler\Database;

use Storm\Chronicler\Exceptions\ConcurrencyException;

class DatabaseConcurrencyFailure extends ConcurrencyException {}
