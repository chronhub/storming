<?php

declare(strict_types=1);

namespace Storm\Chronicler\Http\Controllers;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Storm\Chronicler\Exceptions\StreamAlreadyExists;
use Storm\Chronicler\Exceptions\TransactionAlreadyStarted;
use Storm\Chronicler\Exceptions\TransactionNotStarted;
use Storm\Chronicler\Http\ResponseFactory;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\TransactionalChronicler;
use Storm\Stream\Stream;
use Storm\Stream\StreamName;
use Throwable;

final readonly class CreateStream
{
    public function __construct(
        private Chronicler $chronicler,
        private Factory $validator,
        private ResponseFactory $response
    ) {
    }

    /**
     * @throws ValidationException
     * @throws Throwable
     */
    public function __invoke(Request $request): ResponseFactory
    {
        $this->validator->make($request->all(), ['name' => 'required|string'])->validate();

        $this->createNewStream($request->get('name'));

        return $this->response->withStatusCode(204);
    }

    /**
     * @throws TransactionAlreadyStarted
     * @throws TransactionNotStarted
     * @throws StreamAlreadyExists
     * @throws Throwable
     */
    private function createNewStream(string $streamName): void
    {
        if ($this->chronicler instanceof TransactionalChronicler) {
            $this->chronicler->beginTransaction();
        }

        try {
            $stream = new Stream(new StreamName($streamName));

            $this->chronicler->append($stream);
        } catch (Throwable $e) {
            if ($this->chronicler instanceof TransactionalChronicler) {
                $this->chronicler->rollbackTransaction();
            }

            throw $e;
        }

        if ($this->chronicler instanceof TransactionalChronicler) {
            $this->chronicler->commitTransaction();
        }
    }
}
