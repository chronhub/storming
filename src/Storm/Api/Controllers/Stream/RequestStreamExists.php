<?php

declare(strict_types=1);

namespace Storm\Chronicler\Api\Controllers\Stream;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Storm\Chronicler\Api\ResponseFactory;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Stream\StreamName;
use Throwable;

final readonly class RequestStreamExists
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

        $hasStream = $this->chronicler->hasStream(new StreamName($request->get('name')));

        return $this->response->withStatusCode($hasStream ? 204 : 404);
    }
}
