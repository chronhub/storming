<?php

declare(strict_types=1);

namespace Storm\Attribute\Definition;

use Storm\Attribute\Exception\DefinitionException;

use function array_column;
use function array_count_values;
use function array_filter;
use function count;

final class UniquePriorityValidation extends AbstractMessageHandlerValidation
{
    public const MESSAGE_HAS_MULTIPLE_HANDLERS_WITH_SAME_PRIORITY = 'Message %s has multiple handlers with the same priority';

    /**
     * @throws DefinitionException When a message has multiple handlers with the same priority
     */
    public function validate(array $map): void
    {
        foreach ($map as $messageName => $handlers) {
            if (count($handlers) < 2) {
                continue;
            }

            $duplicates = array_filter(
                array_count_values(array_column($handlers, 'priority')),
                fn (int $count) => $count > 1
            );

            if ($duplicates !== []) {
                throw $this->createException(self::MESSAGE_HAS_MULTIPLE_HANDLERS_WITH_SAME_PRIORITY, $messageName);
            }
        }
    }
}
