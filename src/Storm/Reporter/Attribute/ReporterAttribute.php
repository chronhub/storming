<?php

declare(strict_types=1);

namespace Storm\Reporter\Attribute;

class ReporterAttribute
{
    public function __construct(
        public string $id,
        public string $abstract,
        public string $type,
        public string $mode,
        public array $listeners,
        public ?string $defaultQueue,
        public ?string $tracker,
    ) {
    }

    /**
     * @return array{
     *     id: string,
     *     abstract: string,
     *     type: string,
     *     mode: string,
     *     listeners: array<string>,
     *     queue: null|string,
     *     tracker: null|string,
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'abstract' => $this->abstract,
            'type' => $this->type,
            'mode' => $this->mode,
            'listeners' => $this->listeners,
            'queue' => $this->defaultQueue,
            'tracker' => $this->tracker,
        ];
    }
}
