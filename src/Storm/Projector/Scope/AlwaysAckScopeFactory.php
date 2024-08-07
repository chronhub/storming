<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use Closure;
use Storm\Contract\Message\DomainEvent;
use Storm\Projector\Exception\RuntimeException;

use function is_array;
use function is_callable;

final class AlwaysAckScopeFactory implements ProjectorScopeFactory
{
    private UserStateScope $userStateScope;

    public function __construct(
        protected readonly ProjectorScope $projector,
        /** @var Closure(ProjectorScope $projector): void */
        protected Closure $callback,
    ) {
        if (! is_callable($this->projector)) {
            throw new RuntimeException('Projector scope is not callable');
        }

        $this->userStateScope = new UserStateScope();
    }

    public function handle(DomainEvent $event, ?array $userState = null): ProjectorScope
    {
        $userStateScope = is_array($userState) ? $this->userStateScope->setState($userState) : null;

        $boundEvent = $this->bindReactor(function (): void {});

        ($this->projector)($event, $userStateScope);

        $boundEvent($this->projector);

        ($this->callback)($this->projector);

        return $this->projector;
    }

    private function bindReactor(Closure $reactor): Closure
    {
        return function (ProjectorScope $bindScope) use ($reactor) {
            $boundReactor = Closure::bind($reactor, $bindScope);
            $boundReactor($bindScope->event());
        };
    }
}
