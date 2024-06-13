<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Watcher;

use Closure;
use Illuminate\Support\Sleep;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Workflow\Notification\Checkpoint\ShouldSnapshotCheckpoint;
use Storm\Projector\Workflow\Notification\Management\SnapshotCheckpointCaptured;

/**
 * The SnapshotWatcher class is responsible for determining
 * when to take snapshots based on position and/or time intervals.
 * It subscribes to a notification hub and listens for events that signal
 * when a snapshot should be taken.
 */
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

        $this->assertAtLeastOneValidInterval();

        if ($this->positionInterval) {
            $this->callbacks[] = $this->snapshotOnPosition();
        }

        if ($this->timeInterval) {
            $this->callbacks[] = $this->snapshotOnInterval();
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

    protected function snapshotOnInterval(): Closure
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

    protected function snapshotOnPosition(): Closure
    {
        return fn (Checkpoint $checkpoint): bool => $checkpoint->position % $this->positionInterval === 0;
    }

    protected function assertAtLeastOneValidInterval(): void
    {
        if ($this->positionInterval === null && $this->timeInterval === null) {
            throw new InvalidArgumentException('Provide at least one interval between position and time');
        }

        if ($this->positionInterval !== null && $this->positionInterval < 1) {
            throw new InvalidArgumentException('Position interval must be greater than 0');
        }

        if ($this->timeInterval !== null && $this->timeInterval < 1) {
            throw new InvalidArgumentException('Time interval must be greater than 0');
        }

        if ($this->timeInterval !== null && ! $this->clock) {
            throw new InvalidArgumentException('Clock must be set when time interval is provided');
        }
    }
}
