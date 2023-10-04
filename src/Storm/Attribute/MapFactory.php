<?php

declare(strict_types=1);

namespace Storm\Attribute;

use FilesystemIterator;
use Iterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use ReflectionClass;
use RegexIterator;
use Throwable;

use function get_declared_classes;
use function get_declared_interfaces;
use function preg_match;
use function realpath;
use function sort;

class MapFactory
{
    public function fromDirectories(array $directories): Iterator
    {
        foreach ($directories as $path) {
            $iterator = new RegexIterator(
                new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                ),
                '/^.+\.php$/i',
                RecursiveRegexIterator::GET_MATCH
            );

            foreach ($iterator as $file) {
                $sourceFile = $file[0];

                if (! preg_match('(^phar:)i', (string) $sourceFile)) {
                    $sourceFile = realpath($sourceFile);
                }

                try {
                    require_once $sourceFile;
                } catch (Throwable) {
                    continue;
                }

                $includedFiles[$sourceFile] = true;
            }
        }

        $sortedClasses = get_declared_classes();
        sort($sortedClasses);
        $sortedInterfaces = get_declared_interfaces();
        sort($sortedInterfaces);
        $declared = [...$sortedClasses, ...$sortedInterfaces];

        foreach ($declared as $className) {
            $reflectionClass = new ReflectionClass($className);
            $sourceFile = $reflectionClass->getFileName();
            if (isset($includedFiles[$sourceFile])) {
                yield $className => $reflectionClass;
            }
        }
    }
}
