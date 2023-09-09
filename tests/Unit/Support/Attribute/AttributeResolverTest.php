<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Support\Attribute;

use AllowDynamicProperties;
use Illuminate\Contracts\Container\Container;
use ReflectionException;
use stdClass;
use Storm\Contract\Reporter\Reporter;
use Storm\Reporter\Attribute\AsSubscriber;
use Storm\Reporter\Subscriber\MakeMessage;
use Storm\Support\Attribute\AttributeResolver;

beforeEach(function () {
    $this->container = mock(Container::class);
    $this->container->shouldNotHaveBeenCalled();
    $this->resolver = new AttributeResolver($this->container);
});

afterEach(function () {
    $this->container = null;
    $this->resolver = null;
});

it('resolve attributes from a class', function () {
    $attributes = $this->resolver->forClass(MakeMessage::class);

    expect($attributes)->toHaveCount(1)
        ->and($attributes->first())->toBeInstanceOf(AsSubscriber::class)
        ->and($attributes->first()->eventName)->toBe(Reporter::DISPATCH_EVENT)
        ->and($attributes->first()->priority)->toBe(100000)
        ->and($attributes->first()->method)->toBeNull();
});

it('collect any attributes', function () {
    $attributes = $this->resolver->forClass(stdClass::class);

    expect($attributes)->toHaveCount(1)
        ->and($attributes->first())->toBeInstanceOf(AllowDynamicProperties::class);
});

it('raises exception when class does not exist', function () {
    $this->resolver->forClass('foo');
})->throws(ReflectionException::class, 'Class "foo" does not exist');
