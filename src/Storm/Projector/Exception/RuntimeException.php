<?php

declare(strict_types=1);

namespace Storm\Projector\Exception;

use Storm\Contract\Projector\ProjectorFailed;

class RuntimeException extends \RuntimeException implements ProjectorFailed {}
