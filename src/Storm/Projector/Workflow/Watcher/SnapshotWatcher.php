<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Watcher;

use Closure;
use Illuminate\Support\Sleep;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Checkpoint\ShouldSnapshotCheckpoint;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Workflow\Notification\Management\SnapshotCheckpointCaptured;

class SnapshotWatcher
{
    /**
     * @var array<string, Closure>
     */
    protected array $callbacks = [];

    /**
     * @var array<string, int>
     */
    protected array $checkpointCreatedAt = [];

    public function __construct(
        protected ?SystemClock $clock,
        protected readonly ?int $positionInterval,
        protected readonly ?int $timeInterval,
        protected readonly ?int $usleep
    ) {
        if ($positionInterval === null && $timeInterval === null) {
            throw new InvalidArgumentException('Provide at least one interval');
        }

        if ($this->positionInterval && $this->positionInterval < 1) {
            throw new InvalidArgumentException('Position interval must be greater than 0');
        }

        if ($this->timeInterval && $this->timeInterval < 1) {
            throw new InvalidArgumentException('Position interval must be greater than 0');
        }

        if ($this->positionInterval) {
            $this->callbacks[] = $this->onPosition();
        }

        if ($this->timeInterval) {
            $this->callbacks[] = $this->onInterval();
        }
    }

    public function subscribe(NotificationHub $hub): void
    {
        foreach ($this->callbacks as $callback) {
            $hub->addListener(ShouldSnapshotCheckpoint::class,
                function (NotificationHub $hub, ShouldSnapshotCheckpoint $event) use ($callback): void {
                    $isSnapshot = $callback($event->checkpoint);

                    $hub->notifyWhen($isSnapshot, fn () => $hub->trigger(new SnapshotCheckpointCaptured($event->checkpoint)));
                });
        }
    }

    protected function onInterval(): Closure
    {
        return function (Checkpoint $checkpoint): bool {
            $checkpointTime = $this->clock->toDateTimeImmutable($checkpoint->createdAt)->getTimestamp();

            if (! isset($this->checkpointCreatedAt[$checkpoint->streamName])) {
                $this->checkpointCreatedAt[$checkpoint->streamName] = $checkpointTime;
            }

            if (($this->checkpointCreatedAt[$checkpoint->streamName] + $this->timeInterval) < $checkpointTime) {
                unset($this->checkpointCreatedAt[$checkpoint->streamName]);

                return true;
            }

            if ($this->usleep) {
                Sleep::usleep($this->usleep);
            }

            return false;
        };
    }

    protected function onPosition(): Closure
    {
        return fn (Checkpoint $checkpoint): bool => $checkpoint->position % $this->positionInterval === 0;
    }
}
