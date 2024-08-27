<?php

declare(strict_types=1);

namespace Storm\Support\Facade;

use Illuminate\Support\Facades\Facade;
use Storm\Contract\Projector\EmitterProjector;
use Storm\Contract\Projector\ProjectorMonitor;
use Storm\Contract\Projector\QueryProjector;
use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\ReadModelProjector;

/**
 * @method static QueryProjector     query(array $options = [], ?string $connection = null)
 * @method static EmitterProjector   emitter(string $streamName, array $options = [], ?string $connection = null)
 * @method static ReadModelProjector readModel(string $streamName, ReadModel $readModel, array $options = [], ?string $connection = null)
 * @method static ProjectorMonitor   monitor(?string $connection = null)
 */
class Projector extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'projector.manager';
    }
}
