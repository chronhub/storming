<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Builds;

use Storm\Projector\Scope\EmitterScope;
use Storm\Projector\Support\Builder\EmitterProjectorBuilder;

final readonly class ProjectAllStreamBuild
{
    public function __construct(private EmitterProjectorBuilder $builder) {}

    public function __invoke(?string $connection = null): EmitterProjectorBuilder
    {
        return $this->builder
            ->withConnection($connection)
            ->withProjectionName('$all')
            ->withThen(function (EmitterScope $scope): void {
                $scope->emit($scope->event());
            })
            ->fromAll();
    }
}
