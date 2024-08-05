<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Support;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Options\ProjectionOption;
use Storm\Projector\Options\ProvideOption;
use Storm\Projector\Support\Console\OptionalProjectionCommand;

beforeEach(function () {
    $this->command = new class extends Command
    {
        public array $addedOptions = [];

        public function addOption($name, $shortcut = null, $mode = null, $description = '', $default = null): static
        {
            $this->addedOptions[$name] = [
                'shortcut' => $shortcut,
                'mode' => $mode,
                'description' => $description,
                'default' => $default,
            ];

            return $this;
        }
    };

    $this->mockProjection = new class implements ProjectionOption
    {
        use ProvideOption;

        public function jsonSerialize(): array
        {
            return [
                'signal' => true,
                'cacheSize' => 1000,
                'blockSize' => 1000,
                'sleep' => [10000, 5, 1000000],
                'timeout' => 10000,
                'lockout' => 1000000,
                'loadLimiter' => 1000,
                'sleepEmitterOnFirstCommit' => 1000,
                'onlyOnceDiscovery' => false,
                'retries' => [0, 5, 10, 25, 50, 100, 150, 200, 250, 300, 350, 400, 450, 500],
                'recordGap' => false,
                'detectionWindows' => null,
            ];
        }
    };
});

test('configures command with default options', function () {
    $command = $this->command;
    $optionalProjection = new OptionalProjectionCommand();
    $optionalProjection->withDefault($this->mockProjection);

    $projectionOptions = $optionalProjection->configure($command);

    $addedOptions = $command->addedOptions;

    foreach ($projectionOptions as $name => $value) {
        expect($addedOptions[Str::kebab($name)])
            ->toHaveKey('mode', 4)
            ->toHaveKey('shortcut', null)
            ->toHaveKey('default', $optionalProjection->expectValue($value))
            ->toHaveKey('description', ProjectionOption::DESCRIPTIONS[$name]);
    }

    // dump($addedOptions);
});
