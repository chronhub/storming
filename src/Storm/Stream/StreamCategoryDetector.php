<?php

declare(strict_types=1);

namespace Storm\Stream;

use InvalidArgumentException;

use function explode;
use function str_starts_with;
use function strpos;

class StreamCategoryDetector
{
    public const string CATEGORY_SEPARATOR = '-';

    /**
     * Detect stream category from stream name.
     *
     * @throws InvalidArgumentException When stream name starts with a category separator.
     * @throws InvalidArgumentException When stream category is empty.
     * @throws InvalidArgumentException When any of the stream parts is empty.
     */
    public function detect(StreamName $streamName): ?string
    {
        if (str_starts_with($streamName->name, self::CATEGORY_SEPARATOR)) {
            throw new InvalidArgumentException("Stream name $streamName->name cannot start with a category separator");
        }

        $position = strpos($streamName->name, self::CATEGORY_SEPARATOR);

        if ($position === false) {
            return null;
        }

        return $this->determineCategory($streamName);
    }

    protected function determineCategory(StreamName $streamName): string
    {
        $parts = explode(self::CATEGORY_SEPARATOR, $streamName->name);

        foreach ($parts as $part) {
            if (blank($part)) {
                throw new InvalidArgumentException("Invalid stream category $streamName->name");
            }
        }

        return $parts[0];
    }
}
