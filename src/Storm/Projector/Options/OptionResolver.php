<?php

declare(strict_types=1);

namespace Storm\Projector\Options;

use function array_merge;
use function get_class;

final readonly class OptionResolver
{
    public function __construct(private Option|array $options = []) {}

    public function __invoke(array $options = []): Option
    {
        if ($this->options instanceof OptionImmutable) {
            return $this->options;
        }

        if ($this->options instanceof Option) {
            return $options !== [] ? $this->mergeOptions($options) : $this->options;
        }

        return new DefaultOption(...(array_merge($this->options, $options)));
    }

    private function mergeOptions(array $options): Option
    {
        $optionClass = get_class($this->options);

        return new $optionClass(...array_merge($this->options->jsonSerialize(), $options));
    }
}
