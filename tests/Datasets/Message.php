<?php

declare(strict_types=1);

namespace Storm\Tests\DataSets;

use Storm\Tests\Stubs\Double\Message\SomeCommand;
use Storm\Tests\Stubs\Double\Message\SomeEvent;
use Storm\Tests\Stubs\Double\Message\SomeQuery;

dataset('provide messaging', [
    ['command' => new SomeCommand(['name' => 'steph bug'])],
    ['event' => new SomeEvent(['name' => 'steph bug'])],
    ['query' => new SomeQuery(['name' => 'steph bug'])],
]);
