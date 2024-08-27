<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Support;

use Storm\Projector\Support\ReadModel\InteractWithStack;

beforeEach(function () {
    $this->stack = new class()
    {
        use InteractWithStack;

        public array $data = [];

        public function getStack(): array
        {
            return $this->stack;
        }

        protected function foo(string $field, array $data): void
        {
            $this->data[$field] = $data;
        }

        protected function bar(string $field, array $data): void
        {
            $this->data[$field] = $data;
        }
    };
});

it('can interact with stack', function () {
    expect($this->stack->data)->toBeEmpty()
        ->and($this->stack->getStack())->toBeEmpty();

    $this->stack->stack('foo', 'foo', ['args1']);
    $this->stack->stack('bar', 'bar', ['args2']);

    $this->stack->persist();

    expect($this->stack->data)->toBe(['foo' => ['args1'], 'bar' => ['args2']])
        ->and($this->stack->getStack())->toBeEmpty();
});
