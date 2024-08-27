<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Serializer;

use Storm\Serializer\StormSerializer;
use Symfony\Component\Serializer\Serializer;

test('extends symfony serializer', function () {
    $serializer = new StormSerializer();

    expect($serializer)->toBeInstanceOf(Serializer::class);
});
