<?php

declare(strict_types=1);

namespace Storm\Reporter\Attribute;

use Attribute;
use InvalidArgumentException;
use Storm\Contract\Tracker\MessageTracker;
use Storm\Message\DomainType;

#[Attribute(Attribute::TARGET_CLASS)]
class AsReporter
{
    /**
     * Reporter type
     */
    public DomainType $type;

    /**
     * Reporter identifier.
     *
     * Used to bind the reporter into the container
     */
    public string $id;

    /**
     * Whether the reporter is sync or async.
     *
     * Query reporter must be sync
     *
     * When 'sync', all your handlers will be sync, regardless of their handler queue configuration.
     * When 'async', all your handlers will be async, regardless of their handler queue configuration, required the default queue
     * When 'delegate', delegate behavior to the handler queue configuration, the default queue is not used
     * when 'delegate_merge_with_default', delegate behavior to the handler queue configuration, require the default queue
     */
    public Mode $mode;

    /**
     * Listeners to be registered to the reporter.
     *
     * @var array<string>|array
     */
    public array $listeners;

    /**
     * Default queue to be used when dispatching async or when delegate to handler.
     */
    public ?string $defaultQueue;

    /**
     * Tracker to be used when fire events.
     *
     * @see MessageTracker as default tracker
     */
    public ?string $tracker;

    public function __construct(
        string $id,
        string|DomainType $type,
        Mode $mode,
        array $listeners = [],
        ?string $defaultQueue = null,
        ?string $tracker = null,
    ) {
        $this->type = $type instanceof DomainType ? $type : DomainType::from($type);
        $this->id = $id;
        $this->mode = $mode;
        $this->listeners = $listeners;
        $this->defaultQueue = $defaultQueue;
        $this->tracker = $tracker;

        $this->validateType();
        $this->validateEnqueue();
    }

    protected function validateType(): void
    {
        if ($this->type === DomainType::QUERY) {
            if ($this->mode !== Mode::SYNC) {
                throw new InvalidArgumentException('Query reporter must be sync');
            }

            if ($this->defaultQueue !== null) {
                throw new InvalidArgumentException('Query reporter cannot have a default queue');
            }
        }
    }

    protected function validateEnqueue(): void
    {
        if ($this->mode === Mode::ASYNC || $this->mode === Mode::DELEGATE_MERGE) {
            if ($this->defaultQueue === null) {
                throw new InvalidArgumentException('Async and delegate_merge_with_default reporter must have a default queue');
            }
        }
    }
}
