<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector;

use Options\ProjectionOption;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\EmitterProjector;
use Storm\Contract\Projector\EmitterSubscriber;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Contract\Projector\ProjectionQueryScope;
use Storm\Contract\Projector\ProjectorManager;
use Storm\Contract\Projector\ProjectorMonitor;
use Storm\Contract\Projector\QueryProjector;
use Storm\Contract\Projector\QuerySubscriber;
use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\ReadModelProjector;
use Storm\Contract\Projector\ReadModelSubscriber;
use Storm\Contract\Projector\SubscriptionFactory;
use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Projector\ManageProjector;
use Storm\Projector\Monitor;
use Storm\Projector\ProjectEmitter;
use Storm\Projector\ProjectQuery;
use Storm\Projector\ProjectReadModel;
use Storm\Projector\Support\ReadModel\InMemoryReadModel;

beforeEach(function () {
    $this->subscriptionFactory = mock(SubscriptionFactory::class);
    $this->projectorManager = new ManageProjector($this->subscriptionFactory);
});

test('default instance', function () {
    expect($this->projectorManager)->toBeInstanceOf(ManageProjector::class)
        ->and($this->projectorManager)->toBeInstanceOf(ProjectorManager::class);
});

test('new query projector', function (array $options) {
    $projectionOption = mock(ProjectionOption::class);
    $this->subscriptionFactory->expects('createOption')->with($options)->andReturn($projectionOption);
    $this->subscriptionFactory->expects('createQuerySubscription')->with($projectionOption)->andReturn(mock(QuerySubscriber::class));
    $this->subscriptionFactory->expects('createContextBuilder')->andReturn(mock(ContextReader::class));

    $projector = $this->projectorManager->query($options);

    expect($projector)->toBeInstanceOf(ProjectQuery::class)
        ->and($projector)->toBeInstanceOf(QueryProjector::class);
})->with([
    'with empty options' => [[]],
    'with options' => [['foo' => 'bar']],
]);

test('new emitter projector', function (string $streamName, array $options) {
    $projectionOption = mock(ProjectionOption::class);
    $this->subscriptionFactory->expects('createOption')->with($options)->andReturn($projectionOption);
    $this->subscriptionFactory->expects('createEmitterSubscription')->with($streamName, $projectionOption)->andReturn(mock(EmitterSubscriber::class));
    $this->subscriptionFactory->expects('createContextBuilder')->andReturn(mock(ContextReader::class));

    $projector = $this->projectorManager->emitter($streamName, $options);

    expect($projector)->toBeInstanceOf(ProjectEmitter::class)
        ->and($projector)->toBeInstanceOf(EmitterProjector::class);
})
    ->with(['stream1', 'stream2'])
    ->with([
        'with empty options' => [[]],
        'with options' => [['foo' => 'bar']],
    ]);

test('new read model projector', function (string $streamName, ReadModel $readModel, array $options) {
    $projectionOption = mock(ProjectionOption::class);
    $this->subscriptionFactory->expects('createOption')->with($options)->andReturn($projectionOption);
    $this->subscriptionFactory->expects('createReadModelSubscription')->with($streamName, $readModel, $projectionOption)->andReturn(mock(ReadModelSubscriber::class));
    $this->subscriptionFactory->expects('createContextBuilder')->andReturn(mock(ContextReader::class));

    $projector = $this->projectorManager->readModel($streamName, $readModel, $options);

    expect($projector)->toBeInstanceOf(ProjectReadModel::class)
        ->and($projector)->toBeInstanceOf(ReadModelProjector::class);
})
    ->with(['stream1', 'stream2'])
    ->with([
        'mock read model' => mock(ReadModel::class),
        'in memory read model' => fn () => new InMemoryReadModel,
    ])
    ->with([
        'with empty options' => [[]],
        'with options' => [['foo' => 'bar']],
    ]);

test('get query scope', function (?ProjectionQueryScope $scope) {
    $this->subscriptionFactory->expects('getQueryScope')->andReturn($scope);

    expect($this->projectorManager->queryScope())->toBe($scope);
})->with([
    'with null' => null,
    'with mock scope' => mock(ProjectionQueryScope::class),
]);

test('get projector monitor', function () {
    $this->subscriptionFactory->expects('getProjectionProvider')->andReturn(mock(ProjectionProvider::class));
    $this->subscriptionFactory->expects('getSerializer')->andReturn(mock(SymfonySerializer::class));

    expect($this->projectorManager->monitor())->toBeInstanceOf(Monitor::class)
        ->and($this->projectorManager->monitor())->toBeInstanceOf(ProjectorMonitor::class);
});

test('get same projector monitor instance', function () {
    $this->subscriptionFactory->expects('getProjectionProvider')->andReturn(mock(ProjectionProvider::class));
    $this->subscriptionFactory->expects('getSerializer')->andReturn(mock(SymfonySerializer::class));

    $monitor = $this->projectorManager->monitor();

    expect($this->projectorManager->monitor())->toBe($monitor)
        ->and($this->projectorManager->monitor())->toBe($monitor);
});
