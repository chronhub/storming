<?php

declare(strict_types=1);

namespace Storm\Tests\Scope;

use ArrayAccess;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Scope\UserState;

test('default instance', function () {
    $state = new UserState();

    expect($state)->toBeInstanceOf(ArrayAccess::class)
        ->and($state->all())->toBeEmpty();
});

test('offset unset', function () {
    $state = new UserState(['foo' => 'bar']);
    expect($state->all())->toBe(['foo' => 'bar']);

    $state->offsetUnset('foo');

    expect($state->all())->toBeEmpty();
});
test('has', function () {
    $state = new UserState();
    expect($state->has('foo'))->toBeFalse();

    $state->set('foo', 'bar');
    expect($state->has('foo'))->toBeTrue();
});

test('offset exists', function () {
    $state = new UserState(['foo' => 'bar']);
    expect($state->offsetExists('foo'))->toBeTrue();

    $state->offsetUnset('foo');
    expect($state->offsetExists('foo'))->toBeFalse();
});

test('string', function () {
    $state = new UserState(['foo' => 'bar']);
    expect($state->string('foo'))->toBe('bar');

    $state->set('foo', 123);

    expect($state->string('foo'));
})->throws(InvalidArgumentException::class, 'User state value for key [foo] must be a string, integer given.');
test('all', function () {
    $state = new UserState(['foo' => 'bar']);
    expect($state->all())->toBe(['foo' => 'bar']);
});
test('float', function () {
    $state = new UserState(['foo' => 'bar']);
    expect($state->float('foo'))->toBe(123.45);

    $state->set('foo', 123);

    expect($state->float('foo'));
})->throws(InvalidArgumentException::class, 'User state value for key [foo] must be a float, string given.');

test('offset get', function () {
    $state = new UserState(['foo' => 'bar']);
    expect($state->offsetGet('foo'))->toBe('bar');

    $state->set('foo', 123);

    expect($state->offsetGet('foo'));
});

test('set', function () {
    $state = new UserState(['foo' => 'bar']);
    $state->set('baz', 'bar');

    expect($state->get('foo'))->toBe('bar')
        ->and($state->get('baz'))->toBe('bar')
        ->and($state->get('bar'))->toBeNull()
        ->and($state->all())->toBe(['foo' => 'bar', 'baz' => 'bar']);
});
test('integer', function () {
    $state = new UserState(['foo' => 'bar']);
    expect($state->integer('foo'))->toBe(123);

    $state->set('foo', 123);

    expect($state->integer('foo'));
})->throws(InvalidArgumentException::class, 'User state value for key [foo] must be an integer, string given.');
test('boolean', function () {
    $state = new UserState(['foo' => 'bar']);
    expect($state->boolean('foo'))->toBeTrue();

    $state->set('foo', 123);

    expect($state->boolean('foo'));
})->throws(InvalidArgumentException::class, 'User state value for key [foo] must be a boolean, string given.');

test('offset set', function () {
    $state = new UserState(['foo' => 'bar']);
    $state->offsetSet('baz', 'bar');

    expect($state->get('foo'))->toBe('bar')
        ->and($state->get('baz'))->toBe('bar')
        ->and($state->get('bar'))->toBeNull()
        ->and($state->all())->toBe(['foo' => 'bar', 'baz' => 'bar']);
});
test('get', function () {
    $state = new UserState(['foo' => 'bar']);
    expect($state->get('foo'))->toBe('bar');

    $state->set('foo', 123);

    expect($state->get('foo'))
        ->and($state->get('bar'))->toBeNull();
});

test('push', function () {
    $state = new UserState(['foo' => 'bar']);
    $state->push('some', 'value');
    $state->push('some', 'another');

    expect($state->get('foo'))->toBe('bar')
        ->and($state->get('some'))->toBe(['value', 'another'])
        ->and($state->all())->toBe(['foo' => 'bar', 'some' => ['value', 'another']]);
});

test('array', function () {
    $state = new UserState(['foo' => 'bar']);
    expect($state->array('foo'))->toBe(['bar']);

    $state->set('foo', 123);

    expect($state->array('foo'));
})->throws(InvalidArgumentException::class, 'User state value for key [foo] must be an array, string given.');

test('prepend', function () {
    $state = new UserState(['foo' => 'bar']);
    $state->prepend('some', 'value');
    $state->prepend('some', 'another');

    expect($state->get('foo'))->toBe('bar')
        ->and($state->get('some'))->toBe(['another', 'value'])
        ->and($state->all())->toBe(['foo' => 'bar', 'some' => ['another', 'value']]);
});

test('increment', function () {
    $state = new UserState(['foo' => 1]);
    $state->increment('foo');

    expect($state->get('foo'))->toBe(2);

    $state->increment('foo', -1);

    expect($state->get('foo'))->toBe(3);

    $state->increment('bar', -1);
})->throws(InvalidArgumentException::class, 'User state value for key [bar] must be an integer, NULL given.');

test('decrement', function () {
    $state = new UserState(['foo' => 1]);
    $state->decrement('foo');

    expect($state->get('foo'))->toBe(0);

    $state->decrement('foo', -1);

    expect($state->get('foo'))->toBe(-1);

    $state->decrement('bar', -1);
})->throws(InvalidArgumentException::class, 'User state value for key [bar] must be an integer, NULL given.');
