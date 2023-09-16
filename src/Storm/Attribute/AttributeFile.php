<?php

declare(strict_types=1);

namespace Storm\Attribute;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

use function var_export;

final readonly class AttributeFile
{
    public function __construct(
        private Filesystem $filesystem,
        private string $manifest = __DIR__.'/attributes-map.php',
    ) {
    }

    public function compile(Collection $content): void
    {
        $this->filesystem->replace(
            $this->manifest, '<?php return '.var_export($content->jsonSerialize(), true).';'
        );
    }

    public function delete(): void
    {
        if ($this->filesystem->exists($this->manifest)) {
            $this->filesystem->delete($this->manifest);
        }
    }

    /**
     * @throws FileNotFoundException
     */
    public function get(): Collection
    {
        return collect($this->filesystem->getRequire($this->manifest));
    }

    public function exists(): bool
    {
        return $this->filesystem->exists($this->manifest);
    }

    public function path(): string
    {
        return $this->manifest;
    }
}
