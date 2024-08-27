<?php

declare(strict_types=1);

namespace Storm\Projector;

enum ProjectionStatus: string
{
    case RUNNING = 'running';

    case STOPPING = 'stopping';

    case DELETING = 'deleting';

    case DELETING_WITH_EMITTED_EVENTS = 'deleting_with_emitted_events';

    case RESETTING = 'resetting';

    case IDLE = 'idle';

    /**
     * @return array{
     *     'running',
     *     'stopping',
     *     'deleting',
     *     'deleting_with_emitted_events',
     *     'resetting',
     *     'idle'
     * }
     */
    public static function strings(): array
    {
        $strings = [];

        foreach (self::cases() as $case) {
            $strings[] = $case->value;
        }

        return $strings;
    }
}
