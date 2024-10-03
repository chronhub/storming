<?php

declare(strict_types=1);

namespace Storm\Projector\Support;

use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Storm\Projector\Store\EventRepository;
use Storm\Projector\Store\Events\ProjectionCreated;
use Storm\Projector\Store\Events\ProjectionDeleted;
use Storm\Projector\Store\Events\ProjectionDeletedWithEvents;
use Storm\Projector\Store\Events\ProjectionError;
use Storm\Projector\Store\Events\ProjectionReleased;
use Storm\Projector\Store\Events\ProjectionReset;
use Storm\Projector\Store\Events\ProjectionRestarted;
use Storm\Projector\Store\Events\ProjectionStarted;
use Storm\Projector\Store\Events\ProjectionStopped;

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
