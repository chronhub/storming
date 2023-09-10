<?php

declare(strict_types=1);

namespace Storm\Contract\Reporter;

use React\Promise\PromiseInterface;
use Storm\Contract\Tracker\Listener;
use Storm\Contract\Tracker\MessageTracker;
use Storm\Reporter\Attribute\AsSubscriber;

/**
 * @property-read MessageTracker $tracker
 */
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
     *
     * Object can be class name which implements attribute @see AsSubscriber attribute
     * or an instance of @see Listener
     */
    public function subscribe(object|string ...$messageSubscribers): void;

    public function withSubscriberResolver(callable $subscriberResolver): void;
}
