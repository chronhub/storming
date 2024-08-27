<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Storm\Projector\Options\DefaultOption;
use Storm\Projector\Options\Option;
use Symfony\Component\Console\Input\InputOption;

use function array_merge;
use function implode;
use function is_array;
use function is_bool;

class OptionalProjectionCommand
{
    protected string|Option $option = DefaultOption::class;

    /**
     * Set the default projection option.
     *
     * Defaults to {@see DefaultOption}.
     */
    public function withDefault(Option|string $option): self
    {
        $this->option = $option;

        return $this;
    }

    /**
     * Configure the command with the given options.
     *
     * @return array<Option::*, mixed>
     */
    public function configure(Command $command, array $mergeOptions = []): array
    {
        $options = $this->toArray($mergeOptions);

        foreach ($options as $name => $value) {
            $this->addOption($command, $name, $value);
        }

        return $options;
    }

    /**
     * Return the expected value for the cli option.
     */
    public function expectValue(mixed $value): mixed
    {
        return match (true) {
            is_bool($value) => (int) $value,
            is_array($value) => implode(',', $value),
            default => $value,
        };
    }

    /**
     * Add the option to the command.
     */
    protected function addOption(Command $command, string $name, mixed $value): void
    {
        $command->addOption(
            Str::kebab($name),
            null,
            InputOption::VALUE_OPTIONAL,
            Option::DESCRIPTIONS[$name],
            $this->expectValue($value)
        );
    }

    /**
     * Merge the given options with the default projection options class.
     *
     * @return array<Option::*, mixed>
     */
    protected function toArray(array $options): array
    {
        $projectionOption = $this->option instanceof Option
            ? $this->option
            : new $this->option();

        return array_merge($projectionOption->jsonSerialize(), $options);
    }
}
