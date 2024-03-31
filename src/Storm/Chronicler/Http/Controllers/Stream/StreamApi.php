<?php

declare(strict_types=1);

namespace Storm\Chronicler\Http\Controllers\Stream;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes\Info;
use OpenApi\Attributes\Tag;
use Storm\Chronicler\Exceptions\NoStreamEventReturn;
use Storm\Chronicler\Exceptions\StreamAlreadyExists;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Chronicler\Http\ResponseFactory;
use Throwable;

#[
    Info(
        version: '1.0',
        description: 'Stream API',
        title: 'Stream API',
    ),

    Tag(name: 'Stream', description: 'Stream API')
]
abstract readonly class StreamApi
{
    public function __construct(protected ResponseFactory $responseFactory)
    {
    }

    protected function handleException(Throwable $exception, Request $request): JsonResponse
    {
        return match ($exception::class) {
            NoStreamEventReturn::class => $this->toResponse($exception->getMessage(), 204, $request),
            StreamNotFound::class => $this->toResponse($exception->getMessage(), 404, $request),
            StreamAlreadyExists::class => $this->toResponse($exception->getMessage(), 419, $request),
            ValidationException::class => $this->handleValidationException($exception, $request),
            default => $this->toResponse($exception->getMessage(), 500, $request),
        };
    }

    private function handleValidationException(ValidationException $exception, Request $request): JsonResponse
    {
        return $this->responseFactory
            ->withStatusCode(422, $exception->getMessage())
            ->withErrors($exception->errors())
            ->toResponse($request);
    }

    private function toResponse(string $message, int $status, Request $request): JsonResponse
    {
        return $this->responseFactory->withStatusCode($status, $message)->toResponse($request);
    }
}
