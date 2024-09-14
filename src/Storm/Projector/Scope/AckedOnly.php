<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use Closure;
use Illuminate\Support\Traits\ReflectsClosures;
use ReflectionException;
use Storm\Contract\Message\DomainEvent;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Exception\RuntimeException;

use function array_key_exists;
use function is_a;
use function is_array;
use function is_callable;

final class AckedOnly implements ProjectorScopeFactory
{
    use ReflectsClosures;

    /** @var array<string, Closure> */
    private array $boundReactors;

    private UserState $userStateScope;

    public function __construct(
        /** @var array<string, Closure> */
        protected array $reactors,
        protected readonly ProjectorScope $projector,
        /** @var Closure(ProjectorScope $projector): void */
        protected ?Closure $callback = null,
    ) {
        if (! is_callable($this->projector)) {
            throw new RuntimeException('Projector scope is not callable');
        }

        $this->userStateScope = new UserState;
        $this->boundReactors = [];
        $this->bindReactors($reactors);
    }

    public function handle(DomainEvent $event, ?array $userState = null): ProjectorScope
    {
        $userStateScope = is_array($userState) ? $this->userStateScope->setState($userState) : null;

        if (isset($this->boundReactors[$event::class])) {
            ($this->projector)($event, $userStateScope);

            $this->boundReactors[$event::class]($this->projector);

            return $this->then($this->projector);
        }

        return $this->then(($this->projector)(null, $userStateScope));
    }

    private function then(ProjectorScope $projector): ProjectorScope
    {
        if ($this->callback) {
            ($this->callback)($projector);
        }

        return $projector;
    }

    /**
     * @throws ReflectionException
     * @throws InvalidArgumentException when event reactor is not a subclass of domain event
     * @throws InvalidArgumentException when event reactor already registered
     */
    private function bindReactors(array $reactors): void
    {
        foreach ($reactors as $reactor) {
            $eventClass = $this->firstClosureParameterType($reactor);

            if (! is_a($eventClass, DomainEvent::class, true)) {
                throw new InvalidArgumentException("Event reactor $eventClass must be a subclass of ".DomainEvent::class);
            }

            if (array_key_exists($eventClass, $this->boundReactors)) {
                throw new InvalidArgumentException("Event reactor $eventClass already registered");
            }

            $this->boundReactors[$eventClass] = function (ProjectorScope $bindScope) use ($reactor) {
                $boundReactor = Closure::bind($reactor, $bindScope);
                $boundReactor($bindScope->event());
            };
        }
    }
}
