<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Support\Attribute;

use Illuminate\Container\Container;
use Storm\Contract\Message\MessageFactory;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Serializer\MessageSerializer;
use Storm\Message\GenericMessageFactory;
use Storm\Reporter\Subscriber\MakeMessage;
use Storm\Support\Attribute\AttributeResolver;
use Storm\Support\Attribute\ResolveSubscriber;
use Storm\Tracker\GenericListener;

beforeEach(function () {
    $this->container = Container::getInstance();
    $this->attributeResolver = new AttributeResolver();
    $this->attributeResolver->setContainer($this->container);
    $this->resolveSubscriber = new ResolveSubscriber($this->attributeResolver);
});

afterEach(function () {
    $this->container = null;
    $this->attributeResolver = null;
    $this->resolveSubscriber = null;
});

it('resolve subscriber to a listener', function () {
    $this->container->bind(MessageFactory::class, function () {
        return new GenericMessageFactory(mock(MessageSerializer::class));
    });

    $listener = $this->resolveSubscriber->resolve(MakeMessage::class);

    expect($listener)->toHaveCount(1)
        ->and($listener->first())->toBeInstanceOf(GenericListener::class)
        ->and($listener->first()->name())->toBe(Reporter::DISPATCH_EVENT)
        ->and($listener->first()->priority())->toBe(100000);
});
