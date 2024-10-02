<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use BadMethodCallException;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ContextReader;
use Storm\Projector\Checkpoint\CheckpointRecognition as Recognition;
use Storm\Projector\Checkpoint\Checkpoints;
use Storm\Projector\Checkpoint\GapDetector;
use Storm\Projector\Checkpoint\GapRecorder;
use Storm\Projector\Options\Option;
use Storm\Projector\Support\ExponentialSleep;
use Storm\Projector\Workflow\Component\CheckpointReckoning;
use Storm\Projector\Workflow\Component\Computation;
use Storm\Projector\Workflow\Component\Contextualize;
use Storm\Projector\Workflow\Component\EventStreamBatch;
use Storm\Projector\Workflow\Component\EventStreamDiscovery;
use Storm\Projector\Workflow\Component\HaltOn;
use Storm\Projector\Workflow\Component\InMemoryCheckpoint;
use Storm\Projector\Workflow\Component\Metrics;
use Storm\Projector\Workflow\Component\ProcessedStream;
use Storm\Projector\Workflow\Component\Sprint;
use Storm\Projector\Workflow\Component\StatusHolder;
use Storm\Projector\Workflow\Component\Timer;
use Storm\Projector\Workflow\Component\UserState;

final class Component implements ComponentRegistry
{
    /** @var array<string, object> */
    public array $components;

    public function __construct(
        protected Option $option,
        protected EventStreamProvider $eventStreamProvider,
        protected SystemClock $clock
    ) {
        $this->components = [
            'context' => new Contextualize,
            'discovery' => new EventStreamDiscovery($eventStreamProvider),
            'dispatcher' => new Notify,
            'option' => $option,
            'stream' => new ProcessedStream,
            'recognition' => $this->checkpointRecognition($option),
            'compute' => new Computation,
            'metrics' => new Metrics($option->getBlockSize()),
            'status' => new StatusHolder,
            'sprint' => new Sprint,
            'stop' => new HaltOn,
            'batch' => $this->batchStreamEvent($option),
            'time' => new Timer($clock),
            'userState' => new UserState,
        ];
    }

    public function call(callable $callback): mixed
    {
        return $callback($this);
    }

    public function subscribe(Process $process, ContextReader $context): void
    {
        foreach ($this->components as $component) {
            if ($component instanceof ComponentSubscriber) {
                $component->subscribe($process, $context);
            }
        }
    }

    public function __call(string $name, array $arguments): object
    {
        if (! isset($this->components[$name])) {
            throw new BadMethodCallException("Component $name not found");
        }

        return $this->components[$name];
    }

    protected function batchStreamEvent(Option $option): EventStreamBatch
    {
        $heapSleep = new ExponentialSleep(...$option->getSleep());

        return new EventStreamBatch($heapSleep);
    }

    protected function checkpointRecognition(Option $option): Recognition
    {
        $retries = $option->getRetries();
        $checkpoints = new Checkpoints($option->getRecordGap());

        if ($retries === []) {
            return new InMemoryCheckpoint($checkpoints, $this->clock);
        }

        return new CheckpointReckoning(
            $checkpoints,
            $this->clock,
            new GapDetector($retries),
            new GapRecorder,
        );
    }
}
