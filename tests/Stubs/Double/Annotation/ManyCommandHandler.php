<?php

declare(strict_types=1);

namespace Storm\Tests\Stubs\Double\Annotation;

use Storm\Attribute\Definition\MessageDeclarationScope;
use Storm\Story\Attribute\AsMessageHandler;
use Storm\Tests\Stubs\Double\Message\AnotherCommand;
use Storm\Tests\Stubs\Double\Message\ThirdCommand;

final class ManyCommandHandler
{
    #[AsMessageHandler(priority: 2, scope: MessageDeclarationScope::BelongsToClass)]
    public function createAnotherCommand(AnotherCommand $command): object
    {
        return $command;
    }

    #[AsMessageHandler(priority: 1, scope: MessageDeclarationScope::BelongsToClass)]
    public function createAnotherCommand2(AnotherCommand $command): object
    {
        return $command;
    }

    #[AsMessageHandler]
    public function createThirdCommand(ThirdCommand $command): object
    {
        return $command;
    }
}
