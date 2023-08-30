<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Chronicler;

use Storm\Chronicler\Exceptions\TransactionAlreadyStarted;
use Storm\Chronicler\Exceptions\TransactionNotStarted;
use Storm\Chronicler\TransactionalStreamDraft;

describe('test exception', function (): void {
    it('test draft exception', function (): void {
        $draft = new TransactionalStreamDraft('some event');

        expect($draft->exception())->toBeNull()
            ->and($draft->hasTransactionAlreadyStarted())->toBeFalse()
            ->and($draft->hasTransactionNotStarted())->toBeFalse();
    });

    it('test transaction not started', function (): void {
        $draft = new TransactionalStreamDraft('some event');
        $exception = new TransactionNotStarted('no started');

        expect($draft->hasTransactionNotStarted())->toBeFalse();

        $draft->withRaisedException($exception);

        expect($draft->hasTransactionNotStarted())->toBeTrue();
    });

    it('test transaction already started', function (): void {
        $draft = new TransactionalStreamDraft('some event');
        $exception = new TransactionAlreadyStarted('already started');

        expect($draft->hasTransactionAlreadyStarted())->toBeFalse();

        $draft->withRaisedException($exception);

        expect($draft->hasTransactionAlreadyStarted())->toBeTrue();
    });
});
