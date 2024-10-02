<?php

declare(strict_types=1);

namespace Storm\Projector\Support;

use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Storm\Projector\Repository\EventRepository;
use Storm\Projector\Repository\Events\ProjectionCreated;
use Storm\Projector\Repository\Events\ProjectionDeleted;
use Storm\Projector\Repository\Events\ProjectionDeletedWithEvents;
use Storm\Projector\Repository\Events\ProjectionError;
use Storm\Projector\Repository\Events\ProjectionReleased;
use Storm\Projector\Repository\Events\ProjectionReset;
use Storm\Projector\Repository\Events\ProjectionRestarted;
use Storm\Projector\Repository\Events\ProjectionStarted;
use Storm\Projector\Repository\Events\ProjectionStopped;

class EventRepositoryServiceProvider extends EventServiceProvider
{
    /**
     * The event to listener mappings for the event repository.
     *
     * @see EventRepository
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        ProjectionCreated::class => [],
        ProjectionStarted::class => [],
        ProjectionStopped::class => [],
        ProjectionRestarted::class => [],
        ProjectionReset::class => [],
        ProjectionDeleted::class => [],
        ProjectionDeletedWithEvents::class => [],
        ProjectionReleased::class => [],
        ProjectionError::class => [],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
