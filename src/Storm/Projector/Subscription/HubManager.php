<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\Subscriptor;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Workflow\Notification\Management\EventEmitted;
use Storm\Projector\Workflow\Notification\Management\EventLinkedTo;
use Storm\Projector\Workflow\Notification\Management\ProjectionClosed;
use Storm\Projector\Workflow\Notification\Management\ProjectionDiscarded;
use Storm\Projector\Workflow\Notification\Management\ProjectionFreed;
use Storm\Projector\Workflow\Notification\Management\ProjectionLockUpdated;
use Storm\Projector\Workflow\Notification\Management\ProjectionPersistedWhenThresholdIsReached;
use Storm\Projector\Workflow\Notification\Management\ProjectionRestarted;
use Storm\Projector\Workflow\Notification\Management\ProjectionRevised;
use Storm\Projector\Workflow\Notification\Management\ProjectionRise;
use Storm\Projector\Workflow\Notification\Management\ProjectionStatusDisclosed;
use Storm\Projector\Workflow\Notification\Management\ProjectionStored;
use Storm\Projector\Workflow\Notification\Management\ProjectionSynchronized;
use Storm\Projector\Workflow\Notification\Management\SnapshotCheckpointCaptured;

use function array_key_exists;
use function is_callable;
use function is_object;
use function is_string;

final class HubManager implements NotificationHub
{
    /**
     * @var array<string, array<callable>>
     */
    private array $hooks = [
        ProjectionRise::class => [],
        ProjectionStored::class => [],
        ProjectionClosed::class => [],
        ProjectionRevised::class => [],
        ProjectionDiscarded::class => [],
        ProjectionFreed::class => [],
        ProjectionRestarted::class => [],
        ProjectionLockUpdated::class => [],
        ProjectionSynchronized::class => [],
        ProjectionStatusDisclosed::class => [],
        ProjectionPersistedWhenThresholdIsReached::class => [],
        EventEmitted::class => [],
        EventLinkedTo::class => [],
        SnapshotCheckpointCaptured::class => [],
    ];

    /**
     * @var Collection<string, array<string|callable>>
     */
    private Collection $listeners;

    public function __construct(private readonly Subscriptor $subscriptor)
    {
        $this->listeners = new Collection();
    }

    public function addHook(string $hook, callable $trigger): void
    {
        $this->assertHookIsSupported($hook);

        $this->hooks[$hook][] = $trigger;
    }

    public function addHooks(array $hooks): void
    {
        foreach ($hooks as $hook => $trigger) {
            $this->addHook($hook, $trigger);
        }
    }

    public function trigger(object $hook): void
    {
        $hookClassName = $hook::class;

        $this->assertHookIsSupported($hookClassName);

        foreach ($this->hooks[$hookClassName] as $trigger) {
            $trigger($hook);
        }
    }

    public function addListener(string $event, string|callable|array $callback): void
    {
        $callbacks = Arr::wrap($callback);

        $this->listeners = ! $this->listeners->has($event)
            ? $this->listeners->put($event, $callbacks)
            : $this->listeners->mergeRecursive([$event => $callback]);
    }

    public function addListeners(array $listeners): void
    {
        foreach ($listeners as $event => $callback) {
            $this->addListener($event, $callback);
        }
    }

    public function forgetListener(string $event): void
    {
        $this->listeners = $this->listeners->forget($event);
    }

    public function forgetAll(): void
    {
        $this->listeners = new Collection();
    }

    public function expect(string|object $event, mixed ...$arguments): mixed
    {
        $notification = $this->makeEvent($event, ...$arguments);

        $result = $this->subscriptor->capture($notification);

        // we still pass non-callable event to subscriptor
        // means that event is only a notification, and could hold his own state
        if ($result === $notification) {
            $result = null;
        }

        $this->handleListener($notification, $result);

        return $result;
    }

    public function notify(string|object $event, mixed ...$arguments): void
    {
        $this->expect($event, ...$arguments);
    }

    public function notifyMany(string|object ...$events): void
    {
        foreach ($events as $event) {
            $this->notify($event);
        }
    }

    public function notifyWhen(bool $condition, ?Closure $onSuccess = null, ?Closure $fallback = null): self
    {
        value($condition ? $onSuccess : $fallback, $this);

        return $this;
    }

    private function handleListener(object $event, mixed $result): void
    {
        foreach ($this->listeners->get($event::class, []) as $handler) {
            if (is_string($handler)) {
                $handler = new $handler();
            }

            if (! is_callable($handler)) {
                throw new InvalidArgumentException('Event listener handler must be a callable for event '.$event::class);
            }

            $handler($this, $event, $result);
        }
    }

    private function makeEvent(string|object $notification, mixed ...$arguments): object
    {
        if (is_object($notification)) {
            return $notification;
        }

        return new $notification(...$arguments);
    }

    private function assertHookIsSupported(string $hookClassName): void
    {
        if (! array_key_exists($hookClassName, $this->hooks)) {
            throw new InvalidArgumentException("Hook $hookClassName is not supported");
        }
    }
}
