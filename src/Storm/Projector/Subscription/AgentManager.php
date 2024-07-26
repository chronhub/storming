<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Contract\Projector\AgentRegistry;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\WorkflowInterface;
use Storm\Projector\Factory\AgentProvider;
use Storm\Projector\Factory\WorkflowBuilder;

use function is_callable;

final readonly class AgentManager implements AgentRegistry
{
    public function __construct(
        private AgentProvider $agentProvider,
        private WorkflowBuilder $workflowBuilder,
    ) {}

    public function newWorkflow(): WorkflowInterface
    {
        return $this->workflowBuilder->create($this);
    }

    public function subscribe(NotificationHub $hub, ContextReader $context): void
    {
        $this->agentProvider->subscribe($hub, $context);
    }

    public function capture(callable|object $event): mixed
    {
        return is_callable($event) ? $event($this) : $event;
    }

    /**
     * @mixin AgentProvider
     */
    public function __call(string $name, array $arguments): object
    {
        return $this->agentProvider->{$name}();
    }
}
