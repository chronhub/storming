<?php

declare(strict_types=1);

namespace Storm\Projector\Exception;

use Storm\Contract\Projector\ProjectorFailed;

class InvalidArgumentException extends \InvalidArgumentException implements ProjectorFailed {}
