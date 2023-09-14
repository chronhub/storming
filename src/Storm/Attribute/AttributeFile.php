<?php

declare(strict_types=1);

namespace Storm\Attribute;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use RuntimeException;

final readonly class AttributeFile
{
    public const CACHE_FILE = __DIR__.'/attributes-map.php';

    public function __construct(private Filesystem $filesystem)
    {
    }

    public function compile(Collection $content): void
    {
        if ($this->filesystem->exists(self::CACHE_FILE)) {
            throw new RuntimeException('File already exists, use refresh');
        }

        $this->filesystem->put(self::CACHE_FILE, $content->toJson());
    }

    public function refresh(Collection $content): void
    {
        if ($this->filesystem->exists(self::CACHE_FILE)) {
            $this->filesystem->delete(self::CACHE_FILE);
        }

        $this->compile($content);
    }

    public function get(): Collection
    {
        if ($this->filesystem->missing(self::CACHE_FILE)) {
            throw new FileNotFoundException('File not found: '.self::CACHE_FILE);
        }

        return collect($this->filesystem->json(self::CACHE_FILE));
    }

    public function delete(): void
    {
        if ($this->filesystem->exists(self::CACHE_FILE)) {
            $this->filesystem->delete(self::CACHE_FILE);
        }
    }

    public function exists(): bool
    {
        return $this->filesystem->exists(self::CACHE_FILE);
    }
}
