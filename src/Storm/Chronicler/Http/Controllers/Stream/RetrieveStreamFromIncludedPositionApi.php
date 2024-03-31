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
        path: '/stream/from',
        operationId: 'retrieveStreamFromIncludedPosition',
        description: 'Retrieve stream events from included position with limit',
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
                name: 'limit',
                description: 'limit the number of stream events, must be greater than from',
                in: 'query',
                required: true,
                schema: new Schema(type: 'integer', minimum: 2)
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
final readonly class RetrieveStreamFromIncludedPositionApi extends StreamApi
{
    public function __invoke(Request $request, RetrieveStreamFromIncludedPosition $process): JsonResponse
    {
        try {
            $response = $process($request);
        } catch (Throwable $exception) {
            return $this->handleException($exception, $request);
        }

        return $response->toResponse($request);
    }
}
