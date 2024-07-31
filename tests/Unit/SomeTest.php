<?php

declare(strict_types=1);

namespace Storm\Tests\Unit;

use Storm\Projector\Workflow\Component\Metrics;

beforeEach(function () {
    $this->metrics = new Metrics(10);
});

test('foo', function () {

    expect($this->metrics->main)->toBe(0)
        ->and($this->metrics->processed)->toBe(0)
        ->and($this->metrics->acked)->toBe(0)
        ->and($this->metrics->cycle)->toBe(0);
});

test('bar', function () {
    $this->metrics->incrementBatchStream();

    dump($this->metrics->main);
    dump($this->metrics->processed);
    dump($this->metrics->acked);

});
