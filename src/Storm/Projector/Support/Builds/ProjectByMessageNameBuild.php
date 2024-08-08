<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Builds;

use Illuminate\Support\Str;
use Storm\Projector\Scope\EmitterScope;
use Storm\Projector\Support\Builder\EmitterProjectorBuilder;

final readonly class ProjectByMessageNameBuild
{
    public function __construct(private EmitterProjectorBuilder $builder) {}

    public function __invoke(?string $connection = null): EmitterProjectorBuilder
    {
        return $this->builder
            ->withConnection($connection)
            ->withProjectionName('$by_message_name')
            ->withThen(function (EmitterScope $scope): void {
                $event = $scope->event();
                $eventClass = '$mn-'.Str::replace('\\', '_', $event::class);

                $scope->linkTo($eventClass, $scope->event());
            })
            ->fromAll();
    }
}
