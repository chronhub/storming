<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Chronicler;

use Storm\Chronicler\Exceptions\TransactionAlreadyStarted;
use Storm\Chronicler\Exceptions\TransactionNotStarted;
use Storm\Chronicler\TransactionalStreamDraft;

beforeEach(function () {
    $this->draft = new TransactionalStreamDraft('some_event');
});

afterEach(function () {
    $this->draft = null;
});

describe('exception', function (): void {
    test('accessor', function (): void {
        expect($this->draft->exception())->toBeNull()
            ->and($this->draft->hasTransactionAlreadyStarted())->toBeFalse()
            ->and($this->draft->hasTransactionNotStarted())->toBeFalse();
    });

    test('with transaction not started', function (): void {
        $exception = new TransactionNotStarted('no started');

        expect($this->draft->hasTransactionNotStarted())->toBeFalse();

        $this->draft->withRaisedException($exception);

        expect($this->draft->hasTransactionNotStarted())->toBeTrue();
    });

    test('with transaction already started', function (): void {
        $exception = new TransactionAlreadyStarted('already started');

        expect($this->draft->hasTransactionAlreadyStarted())->toBeFalse();

        $this->draft->withRaisedException($exception);

        expect($this->draft->hasTransactionAlreadyStarted())->toBeTrue();
    });
});
