<?php

declare(strict_types=1);

namespace Storm\Attribute\Definition;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use RuntimeException;

use function array_column;
use function array_count_values;
use function array_filter;
use function array_key_exists;
use function count;
use function usort;

final class MessageBuilder
{
    protected array $messages;

    public function __construct()
    {
        $this->messages = [];
    }

    /**
     * @template T of MessageHandlerDefinition
     *
     * @param Collection<class-string, array<T>|T> $definitions
     * @return Collection{
     *     class-string,
     *     array{class: class-string, method: non-empty-string, priority: int<0,max>, scope: int<0, max>}
     * }
     */
    public function build(Collection $definitions): Collection
    {
        $this
            ->makeDefinitions($definitions)
            ->validateMessageDeclarationScope()
            ->validateUniquePriorityWhenManyHandlers()
            ->sortMessageHandlersByPriority();

        return collect($this->messages);
    }

    private function makeDefinitions(Collection $definitions): self
    {
        $definitions->each(function ($definition) {
            if ($definition instanceof MessageHandlerDefinition) {
                $this->pushToMessages($definition);
            } else {
                foreach ($definition as $def) {
                    $this->pushToMessages($def);
                }
            }
        });

        return $this;
    }

    private function validateMessageDeclarationScope(): self
    {
        foreach ($this->messages as $messageName => $handlers) {
            $counts = [
                MessageDeclarationScope::Unique->value => 0,
                MessageDeclarationScope::BelongsToClass->value => 0,
                MessageDeclarationScope::BelongsToMany->value => 0,
            ];

            $classForBelongsToClass = null;

            foreach ($handlers as $info) {
                $scope = $info['scope'];

                if (! array_key_exists($scope, $counts)) {
                    throw new RuntimeException("Invalid scope $scope for message handler {$info['class']}::{$info['method']}");
                }

                $counts[$scope]++;

                if ($scope === MessageDeclarationScope::BelongsToClass->value) {
                    if ($classForBelongsToClass === null) {
                        $classForBelongsToClass = $info['class'];
                    } elseif ($info['class'] !== $classForBelongsToClass) {
                        throw new RuntimeException("Message $messageName has handlers with different classes for 'BelongsToClass' scope");
                    }
                }
            }

            if ($counts[MessageDeclarationScope::Unique->value] > 1) {
                throw new RuntimeException("Message $messageName has multiple handlers with the 'Unique' scope");
            }

            if ($counts[MessageDeclarationScope::BelongsToClass->value] > 1 && $classForBelongsToClass === null) {
                throw new RuntimeException("Message $messageName has multiple handlers with the 'BelongsToClass' scope, but they have different classes");
            }
        }

        return $this;
    }

    /**
     * @throws InvalidArgumentException When a message has multiple handlers with the same priority
     */
    private function validateUniquePriorityWhenManyHandlers(): self
    {
        foreach ($this->messages as $messageName => $handlers) {
            if (count($handlers) < 2) {
                continue;
            }

            $duplicates = array_filter(
                array_count_values(
                    array_column($handlers, 'priority')
                ), fn (int $count) => $count > 1
            );

            if ($duplicates !== []) {
                throw new RuntimeException("Message $messageName has multiple handlers with the same priority");
            }
        }

        return $this;
    }

    private function sortMessageHandlersByPriority(): self
    {
        foreach ($this->messages as $messageName => $handlers) {
            usort($handlers, function ($a, $b) {
                return $a['priority'] <=> $b['priority'];
            });

            $this->messages[$messageName] = $handlers;
        }

        return $this;
    }

    private function pushToMessages(MessageHandlerDefinition $definition): void
    {
        if (! isset($this->messages[$definition->messageName])) {
            $this->messages[$definition->messageName] = [];
        }

        $this->messages[$definition->messageName][] = $definition->info();
    }
}
