<?php

declare(strict_types=1);

namespace Storm\Support\Facade;

use Illuminate\Support\Facades\Facade;
use React\Promise\PromiseInterface;
use Storm\Contract\Reporter\Reporter;

/**
 * @method static Reporter              get(string $name)
 * @method static PromiseInterface|null relay(array|object $message, ?string $hint = null)
 */
class Report extends Facade
{
    public const REPORTER_ID = 'reporter.manager';

    protected static function getFacadeAccessor(): string
    {
        return self::REPORTER_ID;
    }
}
