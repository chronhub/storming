<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Builds;

use Storm\Projector\Scope\EmitterScope;
use Storm\Projector\Support\Builder\EmitterProjectorBuilder;
use Storm\Stream\StreamName;

final readonly class ProjectByPartitionBuild
{
    public function __construct(private EmitterProjectorBuilder $builder) {}

    public function __invoke(?string $connection = null): EmitterProjectorBuilder
    {
        return $this->builder
            ->withConnection($connection)
            ->withProjectionName('$by_partition')
            ->withThen(function (EmitterScope $scope): void {
                $currentStreamName = $scope->streamName();
                $streamName = new StreamName($currentStreamName);

                if (! $streamName->hasPartition()) {
                    return;
                }

                $scope->linkTo('$ct-'.$streamName->partition(), $scope->event());
            })
            ->fromAll();
    }
}
