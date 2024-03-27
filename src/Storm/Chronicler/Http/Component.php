<?php

declare(strict_types=1);

namespace Storm\Chronicler\Http;

use OpenApi\Attributes\Components;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Schema;

#[Components(
    schemas: [
        new Schema(
            schema: 'Error',
            properties: [
                new Property(property: 'message', type: 'string'),
                new Property(property: 'code', type: 'integer'),
            ],
            type: 'object',
        ),

        new Schema(
            schema: 'ValidationError',
            type: 'array',
            items: new Items(),
        ),
    ],

    responses: [
        new Response(
            response: 422,
            description: 'Bad request',
            content: new JsonContent(
                ref: '#/components/schemas/ValidationError',
                properties: [
                    new Property(
                        property: 'errors',
                        type: 'object',
                    ),
                ],
                type: 'object',
            )
        ),
        new Response(
            response: 401,
            description: 'Authentication failed',
            content: new JsonContent(ref: '#/components/schemas/Error')
        ),
        new Response(
            response: 403,
            description: 'Authorization failed',
            content: new JsonContent(ref: '#/components/schemas/Error')
        ),
        new Response(
            response: 'StreamNotFound',
            description: 'Stream not found',
            content: new JsonContent(ref: '#/components/schemas/Error')
        ),
        new Response(
            response: 'StreamAlreadyExists',
            description: 'Stream already exists',
            content: new JsonContent(ref: '#/components/schemas/Error')
        ),
        new Response(
            response: 500,
            description: 'Internal error',
            content: new JsonContent(ref: '#/components/schemas/Error')
        ),
    ],
)]
final class Component
{
}
