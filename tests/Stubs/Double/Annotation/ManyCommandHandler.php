<?php

declare(strict_types=1);

namespace Storm\Tests\Stubs\Double\Annotation;

use Storm\Reporter\Attribute\AsMessageHandler;
use Storm\Tests\Stubs\Double\Message\AnotherCommand;
use Storm\Tests\Stubs\Double\Message\ThirdCommand;

final class ManyCommandHandler
{
    public array $handlers = [];

    #[AsMessageHandler]
    public function createAnotherCommand(AnotherCommand $command): void
    {
        $this->handlers[] = $command;
    }

    //fixMe message name should be unique
    #[AsMessageHandler]
    public function createThirdCommand(ThirdCommand $command): void
    {
        $this->handlers[] = $command;
    }
}
