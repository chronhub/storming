<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

use Storm\Tests\TestCase;

uses(TestCase::class)->in('Unit');
