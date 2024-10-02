<?php

declare(strict_types=1);

namespace Storm\Projector\Support;

use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Storm\Projector\Storage\EventRepository;
use Storm\Projector\Storage\Events\ProjectionCreated;
use Storm\Projector\Storage\Events\ProjectionDeleted;
use Storm\Projector\Storage\Events\ProjectionDeletedWithEvents;
use Storm\Projector\Storage\Events\ProjectionError;
use Storm\Projector\Storage\Events\ProjectionReleased;
use Storm\Projector\Storage\Events\ProjectionReset;
use Storm\Projector\Storage\Events\ProjectionRestarted;
use Storm\Projector\Storage\Events\ProjectionStarted;
use Storm\Projector\Storage\Events\ProjectionStopped;

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
