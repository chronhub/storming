<?php

declare(strict_types=1);

namespace Storm\Serializer;

use Storm\Contract\Serializer\SymfonySerializer;
use Symfony\Component\Serializer\Serializer;

final class StormSerializer extends Serializer implements SymfonySerializer {}
