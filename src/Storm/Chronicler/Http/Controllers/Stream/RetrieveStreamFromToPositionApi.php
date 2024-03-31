<?php

declare(strict_types=1);

namespace Storm\Chronicler\Http\Controllers\Stream;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Schema;
use Throwable;

#[
    Get(
        path: '/stream/from_to',
        operationId: 'retrieveFromToStreamPosition',
        description: 'Retrieve stream events from included position to next included position',
        tags: ['Stream'],
        parameters: [
            new Parameter(
                name: 'name',
                description: 'Stream name',
                in: 'query',
                required: true,
                schema: new Schema(type: 'string')
            ),
            new Parameter(
                name: 'from',
                description: 'from included stream position',
                in: 'query',
                required: true,
                schema: new Schema(type: 'integer', minimum: 1)
            ),
            new Parameter(
                name: 'to',
                description: 'to included stream position, must be greater than from',
                in: 'query',
                required: true,
                schema: new Schema(type: 'integer', minimum: 2)
            ),
            new Parameter(
                name: 'direction',
                description: 'sort stream by direction',
                in: 'query',
                required: true,
                schema: new Schema(type: 'string', enum: ['asc', 'desc'])
            ),
        ],
        responses: [
            new Response(ref: '#/components/responses/StreamEvents', response: 200),
            new Response(ref: '#/components/responses/NoStreamEventReturned', response: 204),
            new Response(ref: '#/components/responses/401', response: 401),
            new Response(ref: '#/components/responses/403', response: 403),
            new Response(ref: '#/components/responses/StreamNotFound', response: 404),
            new Response(ref: '#/components/responses/500', response: 500),
        ],
    ),
]
final readonly class RetrieveStreamFromToPositionApi extends StreamApi
{
    public function __invoke(Request $request, RetrieveStreamFromToPosition $process): JsonResponse
    {
        try {
            $response = $process($request);
        } catch (Throwable $exception) {
            return $this->handleException($exception, $request);
        }

        return $response->toResponse($request);
    }
}
