<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use Storm\Contract\Projector\AgentManager;
use Storm\Projector\Workflow\Concern\ProvideAgentAccessor;
use Storm\Projector\Workflow\Concern\ProvideListenerHandler;

use function call_user_func_array;

/**
 * @mixin AgentManager
 */
class WorkflowContext
{
    use ProvideAgentAccessor;
    use ProvideListenerHandler;

    public function __construct(
        protected readonly AgentManager $agentManager,
    ) {}

    /**
     * @mixin AgentManager
     */
    public function __call(string $method, array $arguments): mixed
    {
        return call_user_func_array([$this->agentManager, $method], $arguments);
    }
}
