<?php

declare(strict_types=1);

namespace Storm\Tests\Feature;

use ReflectionClass;
use Storm\Attribute\Loader;
use Storm\Reporter\Subscriber\DispatchMessage;
use Storm\Tests\Stubs\Double\Annotation\SomeMessageSubscriberWithRepeatableAttribute;
use Storm\Tracker\ResolvedListener;

it('test', function () {
    /** @var Loader $loader */
    $loader = $this->app[Loader::class];

    dump($loader->getMap()->jsonSerialize());
});

it('register reporters', function () {
    dd($this->app['reporter-command-default']);
});

it('test subscriber', function () {
    $sub = $this->app[DispatchMessage::class];

    expect($sub)->toBeArray()
        ->and($sub)->toHaveCount(1)
        ->and($sub[0])->toBeInstanceOf(ResolvedListener::class)
        ->and($sub[0]->origin())->toBe(DispatchMessage::class);
});

it('test many event in on many subscriber', function () {
    $sub = $this->app[SomeMessageSubscriberWithRepeatableAttribute::class];

    expect($sub)->toBeArray()
        ->and($sub)->toHaveCount(2)
        ->and($sub[0])->toBeInstanceOf(ResolvedListener::class)
        ->and($sub[1])->toBeInstanceOf(ResolvedListener::class)
        ->and($sub[0]->origin())->toBe(SomeMessageSubscriberWithRepeatableAttribute::class);
});

it('find reporter name', function () {

    /** @var Loader $loader */
    $loader = $this->app[Loader::class];

    dump($loader);

});

it('du', function () {

    $class = new ReflectionClass(DispatchMessage::class);

    $methods = $class->getMethods();

    dump($methods);
});

it('files', function () {

});
