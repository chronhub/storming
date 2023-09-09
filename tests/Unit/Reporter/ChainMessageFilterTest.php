<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Reporter;

use stdClass;
use Storm\Contract\Reporter\MessageFilter;
use Storm\Message\Message;
use Storm\Reporter\Filter\ChainMessageFilter;

beforeEach(function () {
    $this->message = new Message(new stdClass());
    $this->mock = mock(MessageFilter::class);
});

afterEach(function () {
    $this->message = null;
    $this->mock = null;
});

it('allow message', function () {
    $this->mock->shouldReceive('allows')->times(3)->with($this->message)->andReturnTrue();

    $filter = new ChainMessageFilter($this->mock, $this->mock, $this->mock);

    expect($filter->allows($this->message))->toBeTrue();
});

it('deny message', function () {
    $this->mock->shouldReceive('allows')->times(2)->with($this->message)->andReturns(true, false);

    $filter = new ChainMessageFilter($this->mock, $this->mock, $this->mock);

    expect($filter->allows($this->message))->toBeFalse();
});
