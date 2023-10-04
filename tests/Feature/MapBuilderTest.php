<?php

declare(strict_types=1);

namespace Feature;

use Storm\Attribute\MapFactory;

it('test', function () {
    $map = (new MapFactory())->fromDirectories([__DIR__.'/../../src', __DIR__.'/../../tests']);
    //$map = ClassMapFactory::fromAutoload(__DIR__.'/../../vendor/autoload.php');

    foreach ($map as $class => $reflectionClass) {
        dump($class);
    }
});
