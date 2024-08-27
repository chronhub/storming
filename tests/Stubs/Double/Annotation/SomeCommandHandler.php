<?php

declare(strict_types=1);

namespace Storm\Tests\Stubs\Double\Annotation;

use Storm\Story\Attribute\AsMessageHandler;
use Storm\Tests\Stubs\Double\Message\SomeCommand;

#[AsMessageHandler]
final class SomeCommandHandler
{
    public array $headers = [];

    public array $content = [];

    public bool $isHandled = false;

    public function __invoke(SomeCommand $command): void
    {
        $this->headers = $command->headers();
        $this->content = $command->toContent();

        $this->isHandled = true;
    }
}
