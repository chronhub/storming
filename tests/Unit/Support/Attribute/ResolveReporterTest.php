<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Support\Attribute;

use Illuminate\Container\Container;
use Storm\Support\Attribute\AttributeResolver;
use Storm\Support\Attribute\ResolveReporter;
use Storm\Tests\Unit\Reporter\Stub\ReportCommandStub;

beforeEach(function () {
    $this->container = Container::getInstance();
    $this->attributeResolver = new AttributeResolver();
    $this->attributeResolver->setContainer($this->container);
    $this->resolveReporter = new ResolveReporter($this->attributeResolver);
});

afterEach(function () {
    $this->container = null;
    $this->attributeResolver = null;
    $this->resolveSubscriber = null;
});

it('should resolve reporter', function () {
    $reporter = $this->resolveReporter->resolve(ReportCommandStub::class);

    expect($reporter)->toBeInstanceOf(ReportCommandStub::class);
});
