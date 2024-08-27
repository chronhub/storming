<?php

declare(strict_types=1);

namespace Storm\Story\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Transactional {}
