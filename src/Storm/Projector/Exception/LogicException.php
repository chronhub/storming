<?php

declare(strict_types=1);

namespace Storm\Projector\Exception;

use Storm\Contract\Projector\ProjectorFailed;

class LogicException extends \LogicException implements ProjectorFailed {}
