<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Subscription;

use stdClass;
use Storm\Projector\Factory\Component\ComponentManager;
use Storm\Projector\Projection\HubManager;

use function get_class;
use function in_array;

beforeEach(function () {
    $this->subscriptor = mock(ComponentManager::class);
    $this->hub = new HubManager($this->subscriptor);
});

function getDummyHook(string $name): object
{
    return new class($name)
    {
        public function __construct(public string $name) {}
    };
}

function getDummyHookHandler(array &$expected, bool $allowDuplicate = false): callable
{
    return function (object $hook) use (&$expected, $allowDuplicate): void {
        if ($allowDuplicate or ! in_array($hook->name, $expected, true)) {
            $expected[] = $hook->name;
        }
    };
}

test('add and trigger hook', function () {
    $expected = [];
    $hook = getDummyHook('foo');
    $handler = getDummyHookHandler($expected);

    $this->hub->addHook(get_class($hook), $handler);
    $this->hub->trigger($hook);

    expect($expected)->toBe(['foo']);
});

test('merge handlers if hook already exists', function () {

    $expected = [];
    $hook = getDummyHook('foo');

    $handler1 = getDummyHookHandler($expected);
    $handler2 = getDummyHookHandler($expected, true);

    $this->hub->addHook(get_class($hook), $handler1);
    $this->hub->addHook(get_class($hook), $handler2);

    $this->hub->trigger($hook);

    expect($expected)->toBe(['foo', 'foo']);
});

test('add many hooks', function () {
    $hook1 = getDummyHook('foo');
    $hook2 = getDummyHook('bar');

    $expected = [];

    $handler1 = getDummyHookHandler($expected);
    $handler2 = getDummyHookHandler($expected);

    $this->hub->addHooks([
        get_class($hook1) => $handler1,
        get_class($hook2) => $handler2,
    ]);

    $this->hub->trigger($hook1);
    $this->hub->trigger($hook2);

    expect($expected)->toBe(['foo', 'bar']);
});

test('do nothing if hook does not exists on trigger', function () {

    $expected = [];
    $hook = getDummyHook('foo');
    $handler = getDummyHookHandler($expected);

    $this->hub->addHook(get_class($hook), $handler);
    $this->hub->trigger(new stdClass);

    expect($expected)->toBeEmpty();
});
