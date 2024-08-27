<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use ArrayAccess;
use Closure;
use Illuminate\Support\Arr;
use Storm\Projector\Exception\InvalidArgumentException;

use function abs;
use function array_unshift;
use function gettype;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function sprintf;

class UserState implements ArrayAccess
{
    public function __construct(protected array $state = []) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->state, $key, $default);
    }

    public function set(string|array $key, mixed $value = null): void
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            Arr::set($this->state, $key, $value);
        }
    }

    public function prepend(string $key, mixed $value): self
    {
        $array = $this->get($key, []);

        array_unshift($array, $value);

        $this->set($key, $array);

        return $this;
    }

    public function push(string $key, mixed $value): self
    {
        $array = $this->get($key, []);

        $array[] = $value;

        $this->set($key, $array);

        return $this;
    }

    public function increment(string $key, int $step = 1): self
    {
        $value = $this->integer($key, null);

        $value += abs($step);

        $this->set($key, $value);

        return $this;
    }

    public function decrement(string $key, int $step = 1): self
    {
        $value = $this->integer($key, null);

        $value -= abs($step);

        $this->set($key, $value);

        return $this;
    }

    /**
     * @param (Closure():(string|null))|string|null $default
     */
    public function string(string $key, mixed $default = null): string
    {
        $value = $this->get($key, $default);

        if (! is_string($value)) {
            throw new InvalidArgumentException(sprintf(
                'User state value for key [%s] must be a string, %s given.', $key, gettype($value))
            );
        }

        return $value;
    }

    /**
     * @param (Closure():(int|null))|int|null $default
     */
    public function integer(string $key, mixed $default = null): int
    {
        $value = $this->get($key, $default);

        if (! is_int($value)) {
            throw new InvalidArgumentException(sprintf(
                'User state value for key [%s] must be an integer, %s given.', $key, gettype($value))
            );
        }

        return $value;
    }

    /**
     * @param (Closure():(float|null))|float|null $default
     */
    public function float(string $key, mixed $default = null): float
    {
        $value = $this->get($key, $default);

        if (! is_float($value)) {
            throw new InvalidArgumentException(sprintf(
                'User state value for key [%s] must be a float, %s given.', $key, gettype($value))
            );
        }

        return $value;
    }

    /**
     * @param (Closure():(bool|null))|bool|null $default
     */
    public function boolean(string $key, mixed $default = null): bool
    {
        $value = $this->get($key, $default);

        if (! is_bool($value)) {
            throw new InvalidArgumentException(sprintf(
                'User state value for key [%s] must be a boolean, %s given.', $key, gettype($value))
            );
        }

        return $value;
    }

    /**
     * @param  (Closure():(array<array-key, mixed>|null))|array<array-key, mixed>|null $default
     * @return array<array-key, mixed>
     */
    public function array(string $key, mixed $default = null): array
    {
        $value = $this->get($key, $default);

        if (! is_array($value)) {
            throw new InvalidArgumentException(sprintf(
                'User state value for key [%s] must be an array, %s given.', $key, gettype($value))
            );
        }

        return $value;
    }

    public function setState(array $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function all(): array
    {
        return $this->state;
    }

    public function has(string $key): bool
    {
        return Arr::has($this->state, $key);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        Arr::forget($this->state, $offset);
    }
}
