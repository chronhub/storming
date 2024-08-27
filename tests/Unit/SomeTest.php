<?php

declare(strict_types=1);

namespace Storm\Tests\Unit;

use stdClass;

test('foo', function () {
    $a = new stdClass;
    $a->priority = 1;
    $a->handler = ['1'];

    $d = new stdClass;
    $d->priority = 10;
    $d->handler = ['10'];

    $b = new stdClass;
    $b->priority = 2;
    $b->handler = ['2'];

    $handlers = collect([$a, $d, $b])
        ->sortByDesc('priority')
        ->map(function ($data) {
            return $data->handler;
        });

    dd($handlers);

});
