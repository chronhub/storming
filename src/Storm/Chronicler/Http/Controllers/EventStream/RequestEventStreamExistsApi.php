<?php

declare(strict_types=1);

namespace Storm\Chronicler\Http\Controllers\EventStream;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Schema;
use Storm\Chronicler\Http\Controllers\EventStreamApi;
use Throwable;

#[Get(
    path: '/event-stream',
    summary: 'Check if an event stream exists',
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
        new Response(ref: '#/components/responses/StreamNotFound', response: 404),
        new Response(ref: '#/components/responses/422', response: 422),
        new Response(ref: '#/components/responses/500', response: 500),
    ],
)]
final readonly class RequestEventStreamExistsApi extends EventStreamApi
{
    public function __invoke(Request $request, RequestEventStreamExists $requestEventStreamExists): JsonResponse
    {
        try {
            $response = $requestEventStreamExists->process($request);
        } catch (Throwable $exception) {
            return $this->handleException($exception, $request);
        }

        return $response->toResponse($request);
    }
}
