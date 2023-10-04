<?php

declare(strict_types=1);

namespace Storm\Contract\Reporter;

use React\Promise\PromiseInterface;
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

    /**
     * @template T of object|non-empty-string
     *
     * @param T ...$messageSubscribers
     */
    public function subscribe(object|string ...$messageSubscribers): void;

    public function tracker(): MessageTracker;
}
