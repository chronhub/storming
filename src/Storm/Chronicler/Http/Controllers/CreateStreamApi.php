<?php

declare(strict_types=1);

namespace Storm\Chronicler\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\Post;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Schema;
use Throwable;

#[Post(
    path: '/stream',
    summary: 'Create a new stream',
    tags: ['Stream'],
    parameters: [
        new Parameter(
            name: 'name',
            description: 'Stream name',
            in: 'query',
            required: true,
            schema: new Schema(type: 'string'),
        ),
    ],

    responses: [
        new Response(response: 204, description: 'ok'),
        new Response(ref: '#/components/responses/401', response: 401),
        new Response(ref: '#/components/responses/403', response: 403),
        new Response(ref: '#/components/responses/StreamAlreadyExists', response: 419),
        new Response(ref: '#/components/responses/422', response: 422),
        new Response(ref: '#/components/responses/500', response: 500),
    ],
)]
final readonly class CreateStreamApi extends StreamApi
{
    public function __invoke(Request $request, CreateStream $process): JsonResponse
    {
        try {
            $response = $process($request);
        } catch (Throwable $exception) {
            return $this->handleException($exception, $request);
        }

        return $response->toResponse($request);
    }
}
