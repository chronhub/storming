<?php

declare(strict_types=1);

namespace Storm\Reporter;

use Illuminate\Contracts\Foundation\Application;
use React\Promise\PromiseInterface;
use Storm\Annotation\KernelStorage;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Reporter\ReporterManager as Manager;

final readonly class ReporterManager implements Manager
{
    public function __construct(
        private KernelStorage $storage,
        private Application $app
    ) {
    }

    public function get(string $name): Reporter
    {
        return $this->app[$name];
    }

    public function relay(array|object $message, ?string $hint = null): ?PromiseInterface
    {
        $reporter = $this->storage->getReporterByMessage($message, $hint);

        return $this->get($reporter)->relay($message);
    }
}
