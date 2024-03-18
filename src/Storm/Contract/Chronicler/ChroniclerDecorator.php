<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

interface ChroniclerDecorator extends Chronicler
{
    public function innerChronicler(): Chronicler;
}
