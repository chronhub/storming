<?php

declare(strict_types=1);

namespace Storm\Story\Build;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;
use Storm\Story\Exception\StoryViolation;

use function array_fill_keys;
use function array_map;
use function array_unique;
use function class_exists;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function getcwd;
use function implode;
use function is_dir;
use function md5;
use function preg_match;
use function realpath;
use function sys_get_temp_dir;
use function var_export;

class AttributeScanner
{
    protected string $cacheFile;

    /** @var array<class-string, array<string>> */
    protected array $scannedClasses = [];

    public function __construct(
        protected array $directories,
        protected readonly array $attributeClasses
    ) {
        if ($this->directories === []) {
            throw new StoryViolation('At least one source directory must be provided.');
        }

        $this->directories = array_map([$this, 'toAbsolutePath'], $this->directories);

        foreach ($this->directories as $dir) {
            if (! is_dir($dir)) {
                throw new StoryViolation("Invalid directory path: $dir");
            }
        }

        $this->cacheFile = sys_get_temp_dir().'/attribute_scan_cache_'.md5(implode('', $this->directories)).'.php';
        $this->loadCache();
    }

    public function scan(): array
    {
        if ($this->isCacheValid()) {
            return $this->scannedClasses;
        }

        $this->scannedClasses = array_fill_keys($this->attributeClasses, []);

        foreach ($this->directories as $dir) {
            $this->scanDirectory($dir);
        }

        // Ensure classes are unique
        foreach ($this->scannedClasses as &$scannedClass) {
            $scannedClass = array_unique($scannedClass);
        }

        $this->saveCache();

        return $this->scannedClasses;
    }

    protected function scanDirectory(string $directory): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $className = $this->getFullyQualifiedClassName($file);
            if (! $className) {
                continue;
            }

            $this->scanClassForAttributes($className);
        }
    }

    protected function getFullyQualifiedClassName(SplFileInfo $file): ?string
    {
        $contents = file_get_contents($file->getRealPath());
        $namespace = '';
        $class = '';

        if (preg_match('/namespace\s+(.+?);/', $contents, $matches)) {
            $namespace = $matches[1];
        }

        if (preg_match('/class\s+(\w+)/', $contents, $matches)) {
            $class = $matches[1];
        }

        if ($namespace && $class) {
            return $namespace.'\\'.$class;
        }

        return null;
    }

    protected function scanClassForAttributes(string $className): void
    {
        if (! class_exists($className)) {
            return;
        }

        $reflection = new ReflectionClass($className);

        foreach ($this->attributeClasses as $attributeClass) {
            if (! empty($reflection->getAttributes($attributeClass))) {
                $this->scannedClasses[$attributeClass][] = $className;
            } else {
                foreach ($reflection->getMethods() as $method) {
                    if (! empty($method->getAttributes($attributeClass))) {
                        $this->scannedClasses[$attributeClass][] = $className;

                        break;
                    }
                }
            }
        }
    }

    protected function loadCache(): void
    {
        if (file_exists($this->cacheFile)) {
            $this->scannedClasses = include $this->cacheFile;
        }
    }

    protected function saveCache(): void
    {
        file_put_contents($this->cacheFile, '<?php return '.var_export($this->scannedClasses, true).';');
    }

    protected function isCacheValid(): bool
    {
        if (! file_exists($this->cacheFile)) {
            return false;
        }

        $cacheTime = filemtime($this->cacheFile);

        foreach ($this->directories as $srcDir) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($srcDir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getMTime() > $cacheTime) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function toAbsolutePath(string $path): string
    {
        if (! $this->isAbsolutePath($path)) {
            $path = getcwd().DIRECTORY_SEPARATOR.$path;
        }

        return realpath($path) ?: $path;
    }

    protected function isAbsolutePath(string $path): bool
    {
        return $path[0] === DIRECTORY_SEPARATOR ||
            preg_match('~\A[A-Z]:(?![^/\\\\])~i', $path) > 0;
    }
}
