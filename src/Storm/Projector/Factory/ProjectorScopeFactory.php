<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Closure;
use Illuminate\Support\Traits\ReflectsClosures;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\ProjectorScope;
use Storm\Projector\Exception\RuntimeException;
use Storm\Projector\Scope\UserStateScope;

use function is_a;
use function is_array;
use function is_callable;

class ProjectorScopeFactory
{
    use ReflectsClosures;

    protected array $boundReactors;

    public function __construct(
        protected array $reactors,
        protected readonly ProjectorScope $projector,
        /** @var Closure(ProjectorScope $projector): void */
        protected ?Closure $callback = null,
    ) {
        $this->boundReactors = $this->bindReactors($reactors);
    }

    public function handle(DomainEvent $event, ?array $userState = null): ProjectorScope
    {
        if (! is_callable($this->projector)) {
            throw new RuntimeException('Projector scope is not callable');
        }

        $userStateScope = is_array($userState) ? new UserStateScope($userState) : null;

        if (isset($this->boundReactors[$event::class])) {
            ($this->projector)($event, $userStateScope);

            $this->boundReactors[$event::class]($this->projector);

            return $this->then($this->projector);
        }

        return $this->then(($this->projector)(null, $userStateScope));
    }

    protected function then(ProjectorScope $projector): ProjectorScope
    {
        if ($this->callback) {
            ($this->callback)($projector);
        }

        return $projector;
    }

    protected function bindReactors(array $reactors): array
    {
        $boundReactors = [];

        foreach ($reactors as $reactor) {
            $eventClass = $this->firstClosureParameterType($reactor);

            if (! is_a($eventClass, DomainEvent::class, true)) {
                throw new RuntimeException("Event reactor $eventClass must be a subclass of ".DomainEvent::class);
            }

            if (isset($boundReactors[$eventClass])) {
                throw new RuntimeException("Event reactor $eventClass already registered");
            }

            $boundReactors[$eventClass] = function (ProjectorScope $bindScope) use ($reactor) {
                $boundReactor = Closure::bind($reactor, $bindScope);
                $boundReactor($bindScope->event());
            };
        }

        return $boundReactors;
    }
}
