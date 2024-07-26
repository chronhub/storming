<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use BadMethodCallException;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\CheckpointRecognition;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ProjectionOption;
use Storm\Contract\Projector\ShouldAgentSubscribe;
use Storm\Projector\Checkpoint\Checkpoints;
use Storm\Projector\Checkpoint\GapDetector;
use Storm\Projector\Checkpoint\GapRules;
use Storm\Projector\Support\AckedCounter;
use Storm\Projector\Support\CycleCounter;
use Storm\Projector\Support\ExponentialSleep;
use Storm\Projector\Support\MainCounter;
use Storm\Projector\Support\ProcessedCounter;
use Storm\Projector\Workflow\Agent\CheckpointAgent;
use Storm\Projector\Workflow\Agent\CheckpointGapLessAgent;
use Storm\Projector\Workflow\Agent\ContextReaderAgent;
use Storm\Projector\Workflow\Agent\EventStreamDiscoveryAgent;
use Storm\Projector\Workflow\Agent\ProcessedStreamAgent;
use Storm\Projector\Workflow\Agent\ProjectionStatusAgent;
use Storm\Projector\Workflow\Agent\ReportAgent;
use Storm\Projector\Workflow\Agent\SprintAgent;
use Storm\Projector\Workflow\Agent\StopAgent;
use Storm\Projector\Workflow\Agent\StreamEventAgent;
use Storm\Projector\Workflow\Agent\TimeAgent;
use Storm\Projector\Workflow\Agent\UserStateAgent;
use Storm\Projector\Workflow\Timer;

class AgentProvider
{
    /** @var array<string, object> */
    public array $agents;

    public function __construct(
        protected ProjectionOption $option,
        protected EventStreamProvider $eventStreamProvider,
        protected SystemClock $clock
    ) {
        $this->agents = [
            'context' => new ContextReaderAgent(),
            'discovery' => new EventStreamDiscoveryAgent($eventStreamProvider),
            'option' => $option,
            'processedStream' => new ProcessedStreamAgent(),
            'recognition' => $this->checkpointRecognitionWatcher($option),
            'report' => new ReportAgent(
                new MainCounter(),
                new ProcessedCounter($option->getBlockSize()),
                new AckedCounter(),
                new CycleCounter(),
            ),
            'status' => new ProjectionStatusAgent(),
            'sprint' => new SprintAgent(),
            'stop' => new StopAgent(),
            'streamEvent' => $this->streamEventWatcher($option),
            'time' => new TimeAgent(new Timer($clock)),
            'userState' => new UserStateAgent(),
        ];
    }

    public function subscribe(NotificationHub $hub, ContextReader $context): void
    {
        foreach ($this->agents as $agent) {
            if ($agent instanceof ShouldAgentSubscribe) {
                $agent->subscribe($hub, $context);
            }
        }
    }

    public function __call(string $name, array $arguments): object
    {
        if (! isset($this->agents[$name])) {
            throw new BadMethodCallException("Projection agent $name not found");
        }

        return $this->agents[$name];
    }

    protected function streamEventWatcher(ProjectionOption $option): StreamEventAgent
    {
        $heapSleep = new ExponentialSleep(...$option->getSleep());

        return new StreamEventAgent($heapSleep);
    }

    protected function checkpointRecognitionWatcher(ProjectionOption $option): CheckpointRecognition
    {
        $retries = $option->getRetries();
        $checkpoints = new Checkpoints($option->getRecordGap());

        if ($retries === []) {
            return new CheckpointGapLessAgent($checkpoints, $this->clock);
        }

        $gapDetector = new GapDetector($retries);

        return new CheckpointAgent($checkpoints, $gapDetector, new GapRules(), $this->clock);
    }
}
