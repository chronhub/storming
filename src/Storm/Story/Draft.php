<?php

declare(strict_types=1);

namespace Storm\Story;

use React\Promise\PromiseInterface;

final class Draft
{
    private bool $messageHandled = false;

    private ?PromiseInterface $promise = null;

    public function __construct(
        private readonly object $message,
        private array $handlers,
        public readonly ?object $job = null
    ) {}

    public function getMessage(): object
    {
        return $this->message;
    }

    public function getOnceHandlers(): array
    {
        $handlers = $this->handlers;

        $this->handlers = [];

        return $handlers;
    }

    public function setPromise(PromiseInterface $promise): void
    {
        $this->promise = $promise;
    }

    public function getPromise(): ?PromiseInterface
    {
        return $this->promise;
    }

    public function markHandled(): void
    {
        $this->messageHandled = true;
    }

    public function isHandled(): bool
    {
        return $this->messageHandled;
    }
}
