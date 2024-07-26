<?php

declare(strict_types=1);

namespace Storm\Contract\Reporter;

use React\Promise\PromiseInterface;

interface ReporterManager
{
    /**
     * Get a reporter by name
     *
     * @throw BindingResolutionException when the reporter is not found
     */
    public function get(string $name): Reporter;

    /**
     * Relay a message to a single reporter.
     *
     * Dispatching an event to multiple reporters is not supported.
     *
     * Hint aka message class name is required when the message is an array.
     * checkMe: as an array, it makes aware how the message factory produces the message.
     *
     * @throw InvalidArgumentException when the message is an array and hint is null
     * @throw RuntimeException when multiple reporters are found for the message
     * @throw MessageNotFound when no reporter is found for the message
     *
     * @return PromiseInterface|null a promise only for a query message
     */
    public function relay(array|object $message, ?string $hint = null): ?PromiseInterface;
}
