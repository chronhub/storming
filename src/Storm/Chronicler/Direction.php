<?php

declare(strict_types=1);

namespace Storm\Chronicler;

enum Direction: string
{
    case FORWARD = 'asc';
    case BACKWARD = 'desc';
}
