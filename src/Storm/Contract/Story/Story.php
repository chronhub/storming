<?php

declare(strict_types=1);

namespace Storm\Contract\Story;

use React\Promise\PromiseInterface;

interface Story
{
    /**
     * @return void|PromiseInterface
     */
    public function relay(object|array $payload);
}
