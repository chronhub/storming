<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

interface ChroniclerDecorator extends Chronicler
{
    /**
     * Get the inner chronicler instance being decorated.
     */
    public function innerChronicler(): Chronicler;
}
