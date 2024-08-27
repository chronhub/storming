<?php

declare(strict_types=1);

namespace Storm\Story;

use Storm\Contract\Story\Publisher;
use Storm\Contract\Story\Story;

final readonly class StoryPublisher implements Publisher
{
    public function __construct(private Story $story) {}

    public function relay(object|array $payload): void
    {
        $this->story->relay($payload);
    }
}
