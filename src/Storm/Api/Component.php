<?php

declare(strict_types=1);

namespace Storm\Chronicler\Api;

use OpenApi\Attributes\Components;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Schema;
use Storm\Contract\Message\EventHeader;
use Storm\Contract\Message\Header;

#[Components(
    schemas: [
        // STREAM EVENTS

        new Schema(
            schema: 'BodyData',
            properties: [
                new Property(
                    property: 'data',
                    type: 'array',
                    items: new Items(
                        properties: [
                            new Property(ref: '#/components/schemas/StreamEvents', type: 'object'),
                        ]
                    ),
                ),
            ],
            type: 'object',
        ),

        new Schema(
            schema: 'StreamEvents',
            required: ['position', 'header', 'content'],
            properties: [
                new Property(
                    property: 'position',
                    ref: '#/components/schemas/StreamEventPosition',
                    type: 'object',
                ),
                new Property(
                    property: 'header',
                    ref: '#/components/schemas/StreamEventHeader',
                    type: 'object',
                ),
                new Property(
                    property: 'content',
                    ref: '#/components/schemas/StreamEventContent',
                    type: 'object',
                ),
            ],
            type: 'object',
        ),

        new Schema(
            schema: 'StreamEventPosition',
            required: ['position'],
            properties: [
                new Property(property: 'position', type: 'integer', minimum: 1),
            ],
            type: 'object',
            additionalProperties: true,
        ),
        new Schema(
            schema: 'StreamEventHeader',
            required: [
                Header::EVENT_ID,
                Header::EVENT_TIME,
                Header::EVENT_TYPE,
                EventHeader::AGGREGATE_ID,
                EventHeader::AGGREGATE_ID_TYPE,
                EventHeader::AGGREGATE_TYPE,
                EventHeader::AGGREGATE_VERSION,
                EventHeader::INTERNAL_POSITION,
            ],
            properties: [
                new Property(property: Header::EVENT_ID, type: 'string', format: 'uuid'),
                new Property(property: Header::EVENT_TIME, type: 'string', format: 'date-time'),
                new Property(property: Header::EVENT_TYPE, type: 'string'),
                new Property(property: EventHeader::AGGREGATE_ID, type: 'string', format: 'uuid'),
                new Property(property: EventHeader::AGGREGATE_ID_TYPE, type: 'string'),
                new Property(property: EventHeader::AGGREGATE_TYPE, type: 'string'),
                new Property(property: EventHeader::AGGREGATE_VERSION, type: 'integer', minimum: 1),
                new Property(property: EventHeader::INTERNAL_POSITION, type: 'integer', minimum: 1),
                new Property(property: EventHeader::EVENT_CAUSATION_ID, type: 'string', format: 'uuid'),
                new Property(property: EventHeader::EVENT_CAUSATION_TYPE, type: 'string'),
            ],
            type: 'object',
            additionalProperties: true,
        ),

        new Schema(
            schema: 'StreamEventContent',
            type: 'object',
        ),

        // ERRORS
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
            response: 'StreamEvents',
            description: 'Stream events',
            content: new JsonContent(
                ref: '#/components/schemas/BodyData',
                type: 'object',
            )
        ),
        new Response(
            response: 'NoStreamEventReturned',
            description: 'Stream exists but no stream event returned',
        ),
        new Response(
            response: 422,
            description: 'Validation failed',
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
