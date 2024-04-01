<?php

declare(strict_types=1);

namespace Storm\Chronicler\Api\Controllers\Stream;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Storm\Chronicler\Api\ResponseFactory;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Chronicler\Chronicler;
use Throwable;

final readonly class DeleteStream
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
     * @throws StreamNotFound
     */
    public function __invoke(Request $request): ResponseFactory
    {
        $this->validator->make($request->all(), ['name' => 'required|string'])->validate();

        $streamName = $request->get('name');

        $this->chronicler->delete($streamName);

        return $this->response->withStatusCode(204);
    }
}
