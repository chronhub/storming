<?php

declare(strict_types=1);

namespace Storm\Contract\Reporter;

use React\Promise\PromiseInterface;
use Storm\Contract\Message\DomainType;
use Storm\Contract\Tracker\MessageSubscriber;
use Storm\Contract\Tracker\MessageTracker;

interface Reporter
{
    /**
     * @var string
     */
    public const DISPATCH_EVENT = 'dispatch_event';

    /**
     * @var string
     */
    public const FINALIZE_EVENT = 'finalize_event';

    /**
     * @return void|PromiseInterface
     */
    public function relay(object|array $message);

    public function subscribe(MessageSubscriber ...$messageSubscribers): void;

    public function tracker(): MessageTracker;

    /**
     * @return string{DomainType::*}
     */
    public function support(): string;
}
