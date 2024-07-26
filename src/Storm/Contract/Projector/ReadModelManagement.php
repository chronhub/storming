<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

interface ReadModelManagement extends PersistentManagement
{
    /**
     * Get the read model instance.
     */
    public function getReadModel(): ReadModel;
}
