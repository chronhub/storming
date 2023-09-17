<?php

declare(strict_types=1);

namespace Storm\Attribute\Definition;

enum MessageDeclarationScope: int
{
    /**
     * Allow message to be declared in one handler only
     */
    case Unique = 1;

    /**
     * Allow message to be declared in the same class in any methods
     */
    case BelongsToClass = 2;

    /**
     * Allow message to be declared in any classes and methods
     */
    case BelongsToMany = 4;
}
