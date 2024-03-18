<?php

declare(strict_types=1);

namespace Storm\Message\Attribute;

use Attribute;
use InvalidArgumentException;
use JsonSerializable;
use RuntimeException;

use function method_exists;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class AsMessageHandler implements JsonSerializable
{
    /**
     * Reporter identifier for the message handler.
     *
     * For documentation purposes only.
     */
    public string $reporter;

    /**
     * Message name that the handler handles.
     *
     * Only one message per handler is allowed.
     */
    public string $handles;

    /**
     * Handler queues.
     *
     * Only event handler can define many transports.
     */
    public string|array|null $fromQueue = null;

    /**
     * The method to be invoked on the handler class.
     * Defaults to "__invoke" and optional in class methods.
     */
    public ?string $method = null;

    /**
     * Priority of the message handler when multiple handlers exist within the same class or other classes.
     *
     * Must be unique when using multiple handlers within the same class and/or in other classes.
     */
    public int $priority = 0;

    /**
     * Constructor for the AsMessageHandler attribute.
     *
     * @param string            $reporter  Reporter identifier for the message handler.
     * @param string            $handles   Message name that the handler handles.
     * @param string|array|null $fromQueue The name of the queue from which the handler should listen for messages.
     * @param string|null       $method    The method to be invoked on the handler class. Defaults to "__invoke".
     * @param int               $priority  Priority of the message handler when multiple handlers exist only for event handler.
     *
     * @throws InvalidArgumentException If the $reporter property is blank.
     * @throws InvalidArgumentException If the $handles property is blank.
     * @throws InvalidArgumentException If the $priority property is less than zero.
     */
    public function __construct(
        string $reporter,
        string $handles,
        string|array|null $fromQueue = null,
        ?string $method = null,
        int $priority = 0
    ) {
        if (blank($reporter)) {
            throw new InvalidArgumentException('Reporter id cannot be blank');
        }

        if (blank($handles)) {
            throw new InvalidArgumentException('Handles cannot be blank');
        }

        if ($priority < 0) {
            throw new InvalidArgumentException('Priority cannot be less than zero');
        }

        $this->reporter = $reporter;
        $this->handles = $handles;
        $this->fromQueue = $fromQueue;
        $this->method = $method;
        $this->priority = $priority;
    }

    public function jsonSerialize(): array
    {
        return [
            'reporter_id' => $this->reporter,
            'message_name' => $this->handles,
            'message_handler' => [
                'method' => $this->method,
                'type' => method_exists($this, 'type') ? $this->type()->value() : throw new RuntimeException('Missing message handler type method'),
                'priority' => $this->priority,
                'queue' => $this->fromQueue,
            ],
        ];
    }
}
