<?php

declare(strict_types=1);

namespace Storm\Projector\Options;

use Storm\Contract\Projector\ProjectionOption;
use Storm\Contract\Projector\ProjectionOptionImmutable;

use function array_merge;
use function get_class;

final readonly class ProjectionOptionResolver
{
    public function __construct(private ProjectionOption|array $options = []) {}

    public function __invoke(array $options = []): ProjectionOption
    {
        if ($this->options instanceof ProjectionOptionImmutable) {
            return $this->options;
        }

        if ($this->options instanceof ProjectionOption) {
            return $options !== [] ? $this->mergeOptions($options) : $this->options;
        }

        return new DefaultOption(...(array_merge($this->options, $options)));
    }

    private function mergeOptions(array $options): ProjectionOption
    {
        $optionClass = get_class($this->options);

        return new $optionClass(...array_merge($this->options->jsonSerialize(), $options));
    }
}
