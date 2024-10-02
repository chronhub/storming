<?php

declare(strict_types=1);

namespace Storm\Projector\Factory\Component;

use BadMethodCallException;
use Illuminate\Contracts\Support\Arrayable;

class ComponentFactory implements Arrayable
{
    /** @var array<string, object> */
    protected array $components = [];

    public function add(string $name, object $component): self
    {
        $this->components[$name] = $component;

        return $this;
    }

    public function get(string $name): object
    {
        if (! isset($this->components[$name])) {
            throw new BadMethodCallException("Component $name not found");
        }

        return $this->components[$name];
    }

    public function toArray(): array
    {
        return $this->components;
    }
}
